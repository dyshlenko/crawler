<?php

namespace App;

class ContentLoader implements ContentLoaderInterface
{
    static private $instance;

    /**
     * Singleton pattern
     *
     * @return ContentLoader instance of ContentLoader class.
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private $headers = [];

    /**
     * Set headers for all CURL requests.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Get content for all URLs in $urlArray.
     *
     * @param array $urlArray
     *
     * @return array content of URLs
     */
    public function loadContent(array $urlArray): array
    {
        $curlHandlers = [];
        $multiHandler = curl_multi_init();
        foreach ($urlArray as $url) {
            $curlHandlers[$url] = $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            foreach ($this->headers as $header) {
                curl_setopt($ch, CURLOPT_HEADER, $header);
            }
            curl_multi_add_handle($multiHandler, $ch);
        }

        do {
            $status = curl_multi_exec($multiHandler, $active);
            if ($active) {
                curl_multi_select($multiHandler);
            }
        } while ($active && $status === CURLM_OK);

        foreach ($curlHandlers as $ch) {
            curl_multi_remove_handle($multiHandler, $ch);
        }
        curl_multi_close($multiHandler);

        foreach ($curlHandlers as $url => $ch) {
            $curlHandlers[$url] = curl_multi_getcontent($ch);
            curl_close($ch);
        }

        return $curlHandlers;
    }
}