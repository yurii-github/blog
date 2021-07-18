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
    public function testPageIsSuccessful($url)
    {
        $client = $this->_createClient();
        $client->request('GET', $url);
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function urlProvider()
    {
        return [['/'], ['/sitemap.xml']];
    }

    public function testGetLog()
    {
        $client = $this->_createClient();
        $client->request('GET', '/2000-01-01/a-b-c');
        $content = $client->getResponse()->getContent();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<h1>a b c</h1>', $content);
        $this->assertStringContainsString('line 1<br />', $content);
        $this->assertStringContainsString('line 2', $content);
        $this->assertStringContainsString('<time>2000-01-01</time>', $content);
    }
}
