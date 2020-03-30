<?php

namespace Domain;

use InvalidArgumentException;

/**
 * Class Site.
 * Represents general information about the site.
 *
 * @package Domain
 */
class Site
{
    /** @var string $scheme */
    private $scheme;

    /** @var string $host */
    private $host;

    /** @var string|null $port */
    private $port;

    /** @var string|null $username */
    private $username;

    /** @var string|null $password */
    private $password;

    /**
     * Site constructor.
     *
     * @param string $url root URL for parsing
     */
    public function __construct(string $url)
    {
        $parsed         = parse_url($url);
        $this->scheme   = $parsed['scheme'] ?? null;
        $this->host     = $parsed['host'] ?? null;
        $this->port     = $parsed['port'] ?? null;
        $this->username = $parsed['user'] ?? null;
        $this->password = $parsed['pass'] ?? null;

        if (!($this->scheme && $this->host)) {
            throw new InvalidArgumentException('For the root URL, the schema and host must be defined.');
        }
    }

    /**
     * Get site root URL.
     *
     * @return string site root URL.
     */
    public function getSiteRoot(): string
    {
        return $this->updateUrlElements('/');
    }

    /**
     * Add the missing URL elements to the fully qualified URL form.
     *
     * @param string $sourceUrl source URL for load.
     *
     * @return string fully qualified URL.
     */
    public function correctUrl(string $sourceUrl): string
    {
        return $this->updateUrlElements($sourceUrl);
    }

    /**
     * Does the URL belong to the current site?
     *
     * @param string $url
     *
     * @return bool
     */
    public function isInhere(string $url): bool
    {
        $parsed = parse_url($url);

        return
            /** The scheme is not specified or corresponds to http or https. */
            ((!isset($parsed['scheme'])) ||
             (($parsed['scheme'] ?? false) &&
              ((strtolower($parsed['scheme']) === 'http') || (strtolower($parsed['scheme']) === 'https')))) &&

            /** Host is not specified or matches the host of the root page. */
            ((!isset($parsed['host'])) || ($parsed['host'] === $this->host));
    }

    protected function updateUrlElements(string $sourceUrl): string
    {
        $parsed = parse_url($sourceUrl);
        if ($parsed === false) {
            throw new InvalidArgumentException('The URL "' . $sourceUrl . '" is invalid.');
        }

        $url = ($parsed['scheme'] ?? $this->scheme) . '://';

        if ((($parsed['user'] ?? false) && ($parsed['pass'] ?? false)) ||
            (!($parsed['host'] ?? false) && $this->username && $this->password)) {
            $url .= ($parsed['user'] ?? $this->username) . ':' .
                    ($parsed['pass'] ?? $this->password) . '@';
        }

        $url .= ($parsed['host'] ?? $this->host);

        if (($parsed['port'] ?? false) ||
            (!($parsed['host'] ?? false) && $this->port)) {
            $url .= ':' . ($parsed['port'] ?? $this->port);
        }

        $url .= ($parsed['path'] ?? '/') .
                (($parsed['query'] ?? false) ? '?' . $parsed['query'] : '');

        return $url;
    }
}
