<?php

namespace App;

interface ContentLoaderInterface
{
    /**
     * Set headers for all CURL requests.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers): void ;

    /**
     * Get content for all URLs in $urlArray.
     *
     * @param array $urlArray
     *
     * @return array content of URLs
     */
    public function loadContent(array $urlArray): array;
}
