<?php

use App\ContentLoader;
use App\ImgCountHandler;
use Domain\Page;
use Domain\Site;
use Infrastructure\Repository\PageRepository;
use PHPUnit\Framework\TestCase;

class ImgCountHandlerTest extends TestCase
{
    public function testCountImgTagsInEmptyList(): void
    {
        $content = self::EMPTY_CONTENT;
        $handler = new ImgCountHandler(new Site(self::ROOT_URL), self::ROOT_URL, ContentLoader::getInstance());
        $this->assertEquals(0, $this->invokeMethod($handler, 'countImgTags', [&$content]));
    }

    public function testCountImgTags(): void
    {
        $content = self::TEST_PAGE_ROOT;
        $handler = new ImgCountHandler(new Site(self::ROOT_URL), self::ROOT_URL, ContentLoader::getInstance());
        $this->assertEquals(3, $this->invokeMethod($handler, 'countImgTags', [&$content]));
    }

    public function testPageProcessing(): void
    {
        $page    = new Page(self::ROOT_URL);
        $handler = new ImgCountHandler(new Site(self::ROOT_URL), self::ROOT_URL,
                                       ContentLoader::getInstance());
        $content = self::TEST_PAGE_ROOT;

        $this->invokeMethod($handler, 'pageProcessing', [$page, &$content]);

        $this->assertEquals(3, $page->getImgCount());
        foreach ($page->getChildren() as $child) {
            $this->assertContains($child->getUrl(), ['http://www.example.com/',
                                                     'http://www.example.com/users/345120']);
        }
    }

    public function testPageProcessingRecursive(): void
    {
        $loaderStub = $this->createStubForTestPageProcessingRecursive();
        $handler    = new ImgCountHandler(new Site(self::ROOT_URL), self::ROOT_URL, $loaderStub);

        $this->invokeMethod($handler, 'pageProcessingRecursive', [[self::ROOT_URL]]);

        /** @var PageRepository $repository */
        $repository = $this->getInnerPropertyValue($handler, 'repository');

        $controlUrlList = [
            'http://www.example.com/',
            'http://www.example.com/users/345120',
            'http://www.example.com/users/345'
        ];
        $unexpectedUrls = [];
        foreach ($repository->getPagesIterator() as $url => $page) {
            if (($i = array_search($url, $controlUrlList, true)) === false) {
                $unexpectedUrls[] = $url;
            } else {
                unset($controlUrlList[$i]);
            }
        }
        $this->assertEmpty($controlUrlList);
        $this->assertEmpty($unexpectedUrls);

        $this->assertEquals(3, $repository->get('http://www.example.com/')->getImgCount());
        $this->assertEquals(2, $repository->get('http://www.example.com/users/345120')->getImgCount());
        $this->assertEquals(4, $repository->get('http://www.example.com/users/345')->getImgCount());

        $this->assertGreaterThan(0, $repository->get('http://www.example.com/')->getProcessingTime());
        $this->assertGreaterThan(0, $repository->get('http://www.example.com/users/345120')->getProcessingTime());
        $this->assertGreaterThan(0, $repository->get('http://www.example.com/users/345')->getProcessingTime());
    }

    public function testHandle(): void
    {
        $loaderStub = $this->createStubForTestPageProcessingRecursive();
        $handler    = new ImgCountHandler(new Site(self::ROOT_URL), self::ROOT_URL, $loaderStub);

        $report = $handler->handle(self::ROOT_URL);

        /** @var PageRepository $repository */
        $repository = $this->getInnerPropertyValue($handler, 'repository');

        $sorted = [];
        /** @var Page $page */
        foreach ($repository->getPagesIterator() as $page) {
            $sorted[] = [
                $page->getUrl(),
                $page->getImgCount()
            ];
        }
        $this->assertEquals([
                                ['http://www.example.com/users/345', 4],
                                ['http://www.example.com/', 3],
                                ['http://www.example.com/users/345120', 2]
                            ], $sorted);
    }

    private const
        ROOT_URL = 'http://www.example.com',
        EMPTY_CONTENT = '<body><html><div></div></html></body>',
        TEST_PAGE_ROOT = '<html>
  <body>
    <img src="http://www.example.com">Example</img>
    <a href="http://www.example.com">Example</a>
    <div>
        <img src="http://www.stackoverflow.com/users/345120">SO</img> 
        <a href="http://www.stackoverflow.com/users/345120">SO</a> 
    </div> 
    <img src="www.stackoverflow.com/users/345120">SO</img> 
    <a href="//www.stackoverflow.com/users/345120">SO</a> 
    <a href="/users/345120">SO</a> 
  </body>
</html>',
        TEST_PAGE_1 = '<html>
  <body>
    <img src="http://www.example.com">Example</img>
    <a href="http://www.example.com">Example</a>
    <div>
        <img src="http://www.stackoverflow.com/users/345120">SO</img> 
        <a href="http://www.stackoverflow.com/users/345120">SO</a> 
    </div> 
    <a href="//www.stackoverflow.com/users/345120">SO</a> 
    <a href="/users/345">SO</a> 
  </body>
</html>',
        TEST_PAGE_2 = '<html>
  <body>
    <img src="http://www.example.com">Example</img>
    <a href="http://www.example.com">Example</a>
    <div>
        <img src="http://www.stackoverflow.com/users/345120">SO</img> 
        <a href="http://www.stackoverflow.com/users/345120">SO</a> 
    </div> 
    <img src="www.stackoverflow.com/users/345120">SO</img> 
    <a href="//www.stackoverflow.com/users/345120">SO</a> 
    <img src="http://www.example.com">Example</img>
    <a href="/users/345120">SO</a> 
  </body>
</html>';

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object     Instantiated object that we will run method on.
     * @param string  $methodName Method name to call
     * @param array   $parameters Array of parameters to pass into method
     *
     * @return mixed Method return.
     * @throws Throwable
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Return value of a private property using ReflectionClass
     *
     * @param stdClass $instance
     * @param string   $property
     *
     * @return mixed
     * @throws ReflectionException
     */
    private function getInnerPropertyValue($instance, $property)
    {
        $reflector          = new ReflectionClass($instance);
        $reflector_property = $reflector->getProperty($property);
        $reflector_property->setAccessible(true);

        return $reflector_property->getValue($instance);
    }

    private function createStubForTestPageProcessingRecursive()
    {
        $loaderStub = $this->createMock(ContentLoader::class);
        $map        = [
            [
                [], []
            ], [
                ['http://www.example.com/'],
                ['http://www.example.com/' => self::TEST_PAGE_ROOT]
            ], [
                ['http://www.example.com/users/345120'],
                ['http://www.example.com/users/345120' => self::TEST_PAGE_1]
            ], [
                ['http://www.example.com/users/345'],
                ['http://www.example.com/users/345' => self::TEST_PAGE_2]
            ],
        ];
        $loaderStub->method('loadContent')
                   ->willReturnMap($map);

        return $loaderStub;
    }
}
