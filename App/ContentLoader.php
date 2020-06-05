<?php

namespace App;

use InvalidArgumentException;

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

    private $method = 'GET';

    private $data;

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
            if (in_array($this->method, ['POST', 'PUT'], true)) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $this->data);
            }
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

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return ContentLoader
     */
    public function setMethod(string $method): ContentLoader
    {
        $method = strtoupper($method);
        if (in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
            $this->method = $method;
            return $this;
        }

        throw new InvalidArgumentException('Invalid method ' . var_export($method, true));
    }

    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
