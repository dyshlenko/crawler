<?php


namespace Domain;


use Infrastructure\Repository\PageRepository;

class ImgCountReport implements Report
{
    /**
     * @var PageRepository
     */
    protected $repository;

    /**
     * @var string|null
     */
    protected $html;

    public function __construct(PageRepository $repository)
    {
        $this->repository = $repository;
        $repository->order(
            static function (Page $a, Page $b) {
                if ($a->getImgCount() === $b->getImgCount()) {
                    return 0;
                }
                return ($a->getImgCount() > $b->getImgCount()) ? -1 : 1;
            }
        );
    }

    /**
     * Get default report filename
     *
     * @return string
     */
    public function getDefaultFilename(): string
    {
        return 'report_' . date('d.m.Y') . '.html';
    }

    public function getContent(): string
    {
        if ($this->html === null) {
            $strings = '';
            /** @var Page $page */
            foreach ($this->repository->getPagesIterator() as $page) {
                $strings .= "<tr>\n\t<td>{$page->getUrl()}</td>\n\t<td>{$page->getImgCount()}</td>\n\t<td>{$page->getProcessingTime()}</td>\n</tr>\n";
            }

            $this->html =
                "<table>\n<tr>\n\t<th>URL</th>\n\t<th>number of tags &lt;img&gt;</th>\n\t<th>processing time, sec.</th>\n</tr>\n" .
                $strings . "</table>\n";
        }

        return $this->html;
    }
}
