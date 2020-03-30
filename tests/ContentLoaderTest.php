<?php

use PHPUnit\Framework\TestCase;
use App\ContentLoader;

class ContentLoaderTest extends TestCase
{
    public function testLoadEmptyList(): void
    {
        $loader  = ContentLoader::getInstance();
        $content = $loader->loadContent([]);
        $this->assertEquals([], $content);
    }

    public function testLoadOneUrl(): void
    {
        $url     = 'http://example.com/';
        $loader  = ContentLoader::getInstance();
        $content = $loader->loadContent([$url]);
        $this->assertCount(1, $content);
        $this->assertArrayHasKey($url, $content);
        $this->assertNotEmpty($content[$url]);
    }

    public function testLoadFewUrl(): void
    {
        $urls = ['http://example.com/',
                 'http://www.example.com/',
                 'http://example.org/',
                 'http://www.example.org/'];

        $loader  = ContentLoader::getInstance();
        $content = $loader->loadContent($urls);
        $this->assertCount(count($urls), $content);
        foreach ($urls as $url) {
            $this->assertArrayHasKey($url, $content);
            $this->assertInternalType('string', $content[$url]);
            $this->assertNotEmpty($content[$url]);
        }
    }
}
