<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageLogTest extends WebTestCase
{
    public function tes2tPageIsSuccessful()
    {
        $client = $this->createClient();
        $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testGetLog()
    {
        $client = $this->createClient();
        $client->request('GET', '/2000-01-01/a-b-c');
        $content = (string) $client->getResponse()->getContent();
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertStringContainsString('<h1>a b c</h1>', $content);
        $this->assertStringContainsString('line 1<br />', $content);
        $this->assertStringContainsString('line 2', $content);
        $this->assertStringContainsString('<time>2000-01-01</time>', $content);
    }
}
