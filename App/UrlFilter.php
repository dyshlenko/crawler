<?php

namespace App;

use SimpleXMLElement;

/**
 * Class UrlFilter. Highlights URLs of all page links.
 *
 * @package App
 */
class UrlFilter
{
    static private $instance;

    /**
     * Singleton pattern
     *
     * @return UrlFilter instance of ContentLoader class.
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * UrlFilter private constructor. Singleton pattern.
     */
    private function __construct()
    {
    }

    /**
     * Get URLs of all page links.
     *
     * @param string $content
     *
     * @return array
     */
    public function handle(string &$content): ?array
    {
        if (preg_match_all('/<a(?:\s+(?:href=["\'](?P<href>[^"\'<>]+)["\']|title=["\'](?P<title>[^"\'<>]+)["\']|\w+=["\'][^"\'<>]+["\']))+/i',
                           $content, $matches) === false) {     // parsing error
            return null;
        }

        return $matches['href'] ?? [];
    }
}