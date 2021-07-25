<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \App\Controller\DefaultController::index
 * @uses \App\Kernel
 * @uses \App\Log
 * @uses \App\Sitemap
 * @uses \App\Twig\TwigExtension::inlineFilter
 */
class PageIndexTest extends WebTestCase
{
    public function testPageIsSuccessful()
    {
        $client = $this->createClient();
        $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
