<?php

namespace App;

use Domain\ImgCountReport;
use Domain\Page;
use Domain\Report;
use Domain\Site;
use Infrastructure\Repository\PageRepository;
use InvalidArgumentException;

/**
 * Class ImgCountHandler.
 * Implementation of the recursive command for counting the number of tags <img />.
 *
 * @package App
 */
class ImgCountHandler
{
    /** @var Page $rootPage */
    protected $rootPage;

    /** @var PageRepository $repository */
    protected $repository;

    /** @var Site $site */
    protected $site;

    /** @var int $maxDepth The maximum depth of recursion when processing site pages. */
    protected $maxDepth;

    /**
     * @var ContentLoaderInterface
     */
    private $contentLoader;

    /**
     * ImgCountHandler constructor.
     *
     * @param Site                   $site     Site information.
     * @param string                 $rootUrl  Root URL for begin processing.
     * @param ContentLoaderInterface $loader   Content loader.
     * @param array                  $headers  CURL headers for content load.
     * @param int                    $maxDepth The maximum depth of recursion when processing site pages.
     */
    public function __construct(Site $site, string $rootUrl, ContentLoaderInterface $loader, array $headers = [],
                                int $maxDepth = PHP_INT_MAX)
    {
        $this->repository = new PageRepository();
        $this->repository->store($this->rootPage = new Page($site->correctUrl($rootUrl)));

        $this->maxDepth      = $maxDepth;
        $this->site          = $site;
        $this->contentLoader = $loader;
        $loader->setHeaders($headers);
    }

    /**
     * @param string $url
     *
     * @return Report
     */
    public function handle(string $url): Report
    {
        $this->pageProcessingRecursive([$url]);

        return new ImgCountReport($this->repository);
    }

    private function countImgTags(string &$content): int
    {
        preg_match_all('/<img(?>\\s|$)/i', $content, $matches);
        return count($matches[0] ?? []);
    }

    private function pageProcessing(Page $page, string &$content): void
    {
        if (($childrenUrls = $this->correctUrls(UrlFilter::getInstance()->handle($content))) === null) {
            $page->setChildren([])->setImgCount(0);
            $this->echoErrorMsg($page);
        }
        $children = [];

        /** @var string $url */
        foreach ($childrenUrls as $url) {
            $children[] =
            $childrenPage = $this->repository->get($url) ?? new Page($url);
            $this->repository->store($childrenPage);
        }

        $page->setChildren($children)
             ->setImgCount($this->countImgTags($content));
    }

    private function echoErrorMsg(Page $page): void
    {
        switch (preg_last_error()) {
            case PREG_NO_ERROR:
                $errorMsg = 'ошибки отсутствуют.';
                break;

            case PREG_INTERNAL_ERROR:
                $errorMsg = 'произошла внутренняя ошибка PCRE.';
                break;

            case PREG_BACKTRACK_LIMIT_ERROR:
                $errorMsg = 'лимит обратных ссылок был исчерпан.';
                break;

            case PREG_RECURSION_LIMIT_ERROR:
                $errorMsg = 'лимит рекурсии был исчерпан.';
                break;

            case PREG_BAD_UTF8_ERROR:
                $errorMsg = 'ошибка была вызвана поврежденными данными UTF-8 (только при запуске в режиме UTF-8).';
                break;

            case PREG_BAD_UTF8_OFFSET_ERROR:
                $errorMsg =
                    'смещение не соответствует началу корректной кодовой точки UTF-8 (только при запуске в режиме UTF-8).';
                break;

            case PREG_JIT_STACKLIMIT_ERROR:
                $errorMsg = 'последняя функция PCRE завершилась неудачно из-за лимита стека JIT.';
                break;

            default:
                $errorMsg = 'неизвестная ошибка PCRE.';
        }
        echo "\nContent parsing error for URL \"", $page->getUrl(), '": ', $errorMsg, "\n";
    }

    private function correctUrls(?array $urlList): ?array
    {
        if ($urlList === null) {
            return null;
        }

        foreach ($urlList as $i => $url) {
            if ($this->site->isInhere($url)) {
                try {
                    $correctedUrl = $this->site->correctUrl($url);
                    if (($this->repository->get($correctedUrl) ?? new Page($correctedUrl))->isNotProcessed()) {
                        $urlList[$i] = $correctedUrl;
                    } else {
                        unset($urlList[$i]);
                    }
                } catch (InvalidArgumentException $e) {
                    unset($urlList[$i]);
                }
            } else {
                unset($urlList[$i]);
            }
        }

        return array_values($urlList);
    }

    private function pageProcessingRecursive(array $urlList, int $depth = 1): void
    {
        $start = microtime(true);

        $urlList      = $this->correctUrls($urlList) ?? [];
        $contentArray = $this->contentLoader->loadContent($urlList);
        $loadTime     = microtime(true) - $start;

        foreach ($contentArray as $url => $content) {
            $start = microtime(true);
            $page  = $this->repository->get($url) ?? new Page($url);
            if ($page->isNotProcessed()) {
                $this->repository->store($page);
                $this->pageProcessing($page, $content);
                $page->setProcessingTime(microtime(true) - $start + $loadTime);
            }
            unset($contentArray[$url]);
        }

        /** Check max depth level */
        if ($this->maxDepth <= ++$depth) {
            return;
        }

        foreach ($urlList as $url) {
            $page     = $this->repository->get($url);
            $children = $page->getChildren();
            foreach ($children as $i => $page) {
                if ($page->isNotProcessed()) {
                    $children[$i] = $page->getUrl();
                } else {
                    unset($children[$i]);
                }
            }

            $this->pageProcessingRecursive($children, $depth);
        }
    }
}
