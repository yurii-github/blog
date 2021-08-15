<?php

namespace Tests\Unit;

use App\Log;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \App\Sitemap
 * @uses \App\Log
 */
class SitemapTest extends TestCase
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var \App\Sitemap
     */
    protected $sitemap;
    protected $testLogFilename = TESTDIR.'/_data/logs/2000-01-01_a-b-c.txt';
    protected $testLogSitemap = <<<SITEMAP
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url date="2000-01-01" title="a b c" file="2000-01-01_a-b-c.txt">
    <loc>http://localhost/unit-blog/2000-01-01/a-b-c</loc>
    <lastmod>{MDATE}</lastmod>
    <changefreq>never</changefreq>
    <priority>0.5</priority>
  </url>
</urlset>

SITEMAP;

    
    public static function setUpBeforeClass(): void
    {
        if (!is_dir(TESTDIR.'/_data/tmp')) {
            mkdir(TESTDIR.'/_data/tmp');
        }
    }

    
    protected function setUp(): void
    {
        $this->requestStack = $this->getMockBuilder('\Symfony\Component\HttpFoundation\RequestStack')
            ->allowMockingUnknownTypes()
            ->getMock();
        $request = $this->createMock('\Symfony\Component\HttpFoundation\Request');
        $request->method('getSchemeAndHttpHost')->willReturn('http://localhost');
        $request->method('getBaseUrl')->willReturn('/unit-blog');

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->sitemap = new \App\Sitemap(TESTDIR.'/_data/tmp/sitemap.xml', TESTDIR.'/_data/logs', $this->requestStack);
    }

    protected function tearDown(): void
    {
        @unlink(TESTDIR.'/_data/tmp/sitemap.xml');
    }


    public function testGetLogs()
    {
        $loc = 'http://localhost/unit-blog/2000-01-01/a-b-c';
        $logs = $this->sitemap->getLogs();

        $this->assertEquals(1, count($logs));
        $this->assertNotEmpty($logs[$loc]);
        $this->assertInstanceOf(Log::class, $logs[$loc]);
    }

    public function testGetLogByLoc()
    {
        $loc = 'http://localhost/unit-blog/2000-01-01/a-b-c';
        $log = $this->sitemap->getLogByLoc($loc);
        $this->assertInstanceOf(Log::class, $log);
    }

    /**
     * log record does not exist in sitemap.
     */
    public function testGetLogByLocNoLogRecordInSitemap()
    {
        $this->expectException(NotFoundHttpException::class);
        $loc = 'http://localhost/unit-blog/2000-01-01/a-b-c-lalala';
        $log = $this->sitemap->getLogByLoc($loc);
        $this->assertInstanceOf(Log::class, $log);
    }

    /**
     * record exists in sitemap, but file was removed.
     */
    public function testGetLogByLocRecordExistsButNoLogFile()
    {
        $this->expectException(NotFoundHttpException::class);
        $logToRemoveFilename = TESTDIR.'/_data/logs/2017-04-26_to_remove.txt';
        file_put_contents($logToRemoveFilename, "to remove\n\nline 1 content");
        $loc = 'http://localhost/unit-blog/2017-04-26/to-remove';

        $this->sitemap->flush();
        unlink($logToRemoveFilename);

        $this->sitemap->getLogByLoc($loc);
    }

}
