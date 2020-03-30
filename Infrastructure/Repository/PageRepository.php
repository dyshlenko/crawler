<?php

namespace Infrastructure\Repository;

use ArrayIterator;
use Domain\Page;
use RuntimeException;

class PageRepository
{
    /**
     * @var array $pages
     */
    protected $pages = [];

    /**
     * @param Page $page
     */
    public function store(Page $page): void
    {
        $this->pages[$page->getUrl()] = $page;
    }

    /**
     * @param string $url
     *
     * @return Page
     */
    public function get(string $url): ?Page
    {
        return $this->pages[$url] ?? null;
    }

    /**
     * Get Iterator for stored pages.
     *
     * @return ArrayIterator iterator.
     */
    public function getPagesIterator(): ArrayIterator
    {
        return new ArrayIterator($this->pages);
    }

    /**
     * Sort the contents of the repository using the uasort() function. Affects the crawl order
     * of the contents of the repository using an iterator obtained from PageRepository :: getPagesIterator ().
     *
     * @param callable $compareFunction - callable function for uasort function.
     * @throws RuntimeException
     */
    public function order(callable $compareFunction): void
    {
        if (!uasort($this->pages, $compareFunction)) {
            throw new RuntimeException('Error sorting results.');
        }
    }
}
