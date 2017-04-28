<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    protected function _createClient()
    {
        $client = self::createClient();
        $testSitemap = dirname(static::$kernel->getRootDir()).'/web/sitemap_test.xml';
        @unlink($testSitemap);
        return $client;
    }

    /**
     * @dataProvider urlProvider
     */
    public function test_PageIsSuccessful($url)
    {
        $client = $this->_createClient();
        $client->request('GET', $url);
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function urlProvider()
    {
        return [['/'], ['/sitemap.xml']];
    }


    public function test_GetLog()
    {
        $client = $this->_createClient();
        $client->request('GET', '/2000-01-01/a-b-c');
        $content = $client->getResponse()->getContent();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('<h1>a b c</h1>', $content);
        $this->assertContains('line 1<br />', $content);
        $this->assertContains('line 2', $content);
        $this->assertContains('<time>2000-01-01</time>', $content);
    }

}