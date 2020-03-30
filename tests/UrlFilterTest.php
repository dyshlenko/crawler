<?php

use PHPUnit\Framework\TestCase;
use App\UrlFilter;

class UrlFilterTest extends TestCase
{
    public function testEmptyList(): void
    {
        $content = '<body><html><div></div></html></body>';
        $this->assertEquals([], UrlFilter::getInstance()->handle($content));
    }

    public function testFewUrls(): void
    {
        $content = '<html>
  <body>
    <a href="http://www.example.com">Example</a>
    <div>
        <a href="http://www.stackoverflow.com/users/345120">SO</a> 
    </div> 
    <a href="www.stackoverflow.com/users/345120">SO</a> 
    <a href="/users/345120">SO</a> 
  </body>
</html>';

        $this->assertEquals(['http://www.example.com',
                             'http://www.stackoverflow.com/users/345120',
                             'www.stackoverflow.com/users/345120',
                             '/users/345120'],
                            UrlFilter::getInstance()->handle($content));
    }
}
