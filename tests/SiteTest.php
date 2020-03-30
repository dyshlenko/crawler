<?php

use Domain\Site;
use PHPUnit\Framework\TestCase;

class SiteTest extends TestCase
{
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
        $reflection = new \ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testUpdateUrlElements(): void
    {
        $site = new Site('http://www.stackoverflow.com/users/345120');

        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['http://www.stackoverflow.com/users?key=value']));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['//www.stackoverflow.com/users?key=value']));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements', ['/users?key=value']));

        /** Authorization */
        $site = new Site('http://user:pass@www.stackoverflow.com/users/345120');

        $this->assertEquals('http://user1:pass1@www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['http://user1:pass1@www.stackoverflow.com/users?key=value']));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['http://www.stackoverflow.com/users?key=value']));

        $this->assertEquals('http://user1:pass1@www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['//user1:pass1@www.stackoverflow.com/users?key=value']));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['//www.stackoverflow.com/users?key=value']));

        $this->assertEquals('http://user:pass@www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements', ['/users?key=value']));

        /** Port */
        $site = new Site('http://user:pass@www.stackoverflow.com:88/users/345120');

        $this->assertEquals('http://www.stackoverflow.com:99/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['http://www.stackoverflow.com:99/users?key=value']));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['http://www.stackoverflow.com/users?key=value']));

        $this->assertEquals('http://www.stackoverflow.com:99/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['//www.stackoverflow.com:99/users?key=value']));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements',
                                                ['//www.stackoverflow.com/users?key=value']));

        $this->assertEquals('http://user:pass@www.stackoverflow.com:88/users?key=value',
                            $this->invokeMethod($site, 'updateUrlElements', ['/users?key=value']));
    }

    public function testGetSiteRoot(): void
    {
        $site = new Site('http://user:pass@www.stackoverflow.com:88/users/345120');

        $this->assertEquals('http://user:pass@www.stackoverflow.com:88/', $site->getSiteRoot());
    }

    public function testCorrectUrl(): void
    {
        $site = new Site('http://www.stackoverflow.com/users/345120');

        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $site->correctUrl('http://www.stackoverflow.com/users?key=value'));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $site->correctUrl('//www.stackoverflow.com/users?key=value'));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $site->correctUrl('/users?key=value'));

        /** Authorization */
        $site = new Site('http://user:pass@www.stackoverflow.com/users/345120');

        $this->assertEquals('http://user1:pass1@www.stackoverflow.com/users?key=value',
                            $site->correctUrl('http://user1:pass1@www.stackoverflow.com/users?key=value'));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $site->correctUrl('http://www.stackoverflow.com/users?key=value'));

        $this->assertEquals('http://user1:pass1@www.stackoverflow.com/users?key=value',
                            $site->correctUrl('//user1:pass1@www.stackoverflow.com/users?key=value'));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $site->correctUrl('//www.stackoverflow.com/users?key=value'));

        $this->assertEquals('http://user:pass@www.stackoverflow.com/users?key=value',
                            $site->correctUrl('/users?key=value'));

        /** Port */
        $site = new Site('http://user:pass@www.stackoverflow.com:88/users/345120');

        $this->assertEquals('http://www.stackoverflow.com:99/users?key=value',
                            $site->correctUrl('http://www.stackoverflow.com:99/users?key=value'));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $site->correctUrl('http://www.stackoverflow.com/users?key=value'));

        $this->assertEquals('http://www.stackoverflow.com:99/users?key=value',
                            $site->correctUrl('//www.stackoverflow.com:99/users?key=value'));
        $this->assertEquals('http://www.stackoverflow.com/users?key=value',
                            $site->correctUrl('//www.stackoverflow.com/users?key=value'));

        $this->assertEquals('http://user:pass@www.stackoverflow.com:88/users?key=value',
                            $site->correctUrl('/users?key=value'));
    }

    public function testIsInhere(): void
    {
        $site = new Site('http://user:pass@www.stackoverflow.com:88/users');
        $this->assertFalse($site->isInhere('ftp://user:pass@www.stackoverflow.com:88/users'));
        $this->assertFalse($site->isInhere('http://stackoverflow.com/users'));

        $this->assertTrue($site->isInhere('http://www.stackoverflow.com/u/'));
        $this->assertTrue($site->isInhere('https://www.stackoverflow.com/u'));
        $this->assertTrue($site->isInhere('//www.stackoverflow.com/u/'));
        $this->assertTrue($site->isInhere('/u/v'));
    }
}
