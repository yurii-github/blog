<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageIndexTest extends WebTestCase
{
    public function testPageIsSuccessful()
    {
        $client = $this->createClient();
        $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
