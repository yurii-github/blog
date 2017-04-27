<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    protected function tearDown()
    {
    }

    /**
     * @dataProvider urlProvider
     */
    public function test_PageIsSuccessful($url)
    {
        $client = $this->createClient();
        $client->request('GET', $url);
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function urlProvider()
    {
        return [['/'], ['/sitemap.xml']];
    }


    public function test_GetLog()
    {
        $client = $this->createClient();
        $client->request('GET', '/0000-00-00/a-b-c');
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();

        $this->assertContains('<h1>a b c</h1>', $content);
        $this->assertContains('line 1<br />', $content);
        $this->assertContains('line 2', $content);
        $this->assertContains('<time>0000-00-00</time>', $content);
    }

}