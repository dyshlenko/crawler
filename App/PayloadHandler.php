<?php

namespace App;

use Domain\PayloadReport;
use Domain\Report;
use Domain\Site;

/**
 * Class ImgCountHandler.
 * Implementation of the recursive command for counting the number of tags <img />.
 *
 * @package App
 */
class PayloadHandler
{
    /** @var array $repository */
    protected $loadTime;

    /** @var array $details by iterations */
    protected $details = [];

    /** @var Site $site */
    protected $site;

    /** @var int $concurrentRequests */
    protected $concurrentRequests;

    /** @var int $iterations */
    protected $iterations;

    /**
     * @var ContentLoaderInterface
     */
    private $contentLoader;

    /**
     * ImgCountHandler constructor.
     *
     * @param Site          $site   Site information.
     * @param ContentLoader $loader Content loader.
     * @param int           $concurrentRequests
     * @param int           $iterations
     */
    public function __construct(Site $site, ContentLoader $loader, int $concurrentRequests = 1, int $iterations = 100)
    {
        $this->loadTime           = array_fill(0, $this->iterations, null);
        $this->concurrentRequests = $concurrentRequests;
        $this->iterations         = $iterations;
        $this->site               = $site;
        $this->contentLoader      = $loader;
    }

    /**
     * @param string $url
     *
     * @return Report
     */
    public function handle(string $url): Report
    {
        $this->payload();

        return new PayloadReport($this->loadTime, $this->contentLoader->getMethod(), $this->site->getOriginalUrl(),
                                 $this->iterations, $this->concurrentRequests, $this->contentLoader->getHeaders(),
                                 $this->contentLoader->getData(), $this->details);
    }

    private function payload(): void
    {
        $url          = $this->site->getOriginalUrl();
        $requestsList = array_fill(0, $this->concurrentRequests, $url);

        for ($i = 0; $i < $this->iterations; $i++) {
            $details = [];
            $start = microtime(true);
            $this->contentLoader->loadContent($requestsList, $details);
            $this->loadTime[$i] = microtime(true) - $start;
            $this->details[$i]  = $details;
        }
//        var_dump($this->details);
    }
}
