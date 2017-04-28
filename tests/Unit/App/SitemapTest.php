<?php
namespace tests\Unit;


use App\Log;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class Sitemap extends TestCase
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var \App\Sitemap
     */
    protected $sitemap;

    protected $testLogFilename = TESTDIR .'/_data/logs/2000-01-01_a-b-c.txt';
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


    static function setUpBeforeClass()
    {
        if (!is_dir(TESTDIR.'/_data/tmp')) {
            // dir doesn't exist, make it
            mkdir(TESTDIR.'/_data/tmp');
        }
    }

    static function tearDownAfterClass()
    {
        @unlink(TESTDIR.'/_data/tmp');
    }

    protected function setUp()
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

    protected function tearDown()
    {
        @unlink(TESTDIR.'/_data/tmp/sitemap.xml');
    }

    public function test_generate()
    {
        $logMDate = (new \DateTime())->setTimestamp(filemtime($this->testLogFilename))->format('Y-m-d');
        $this->assertEquals(str_replace('{MDATE}', $logMDate, $this->testLogSitemap), $this->sitemap->generate());
    }

    public function test_getLogs()
    {
        $loc = 'http://localhost/unit-blog/2000-01-01/a-b-c';
        $logs = $this->sitemap->getLogs();

        $this->assertEquals(1, count($logs));
        $this->assertNotEmpty($logs[$loc]);
        $this->assertInstanceOf(Log::class, $logs[$loc]);
    }

    public function test_getLogByLoc()
    {
        $loc = 'http://localhost/unit-blog/2000-01-01/a-b-c';
        $log = $this->sitemap->getLogByLoc($loc);
        $this->assertInstanceOf(Log::class, $log);
    }

    /**
     * log record does not exist in sitemap
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function test_getLogByLoc_NoLogRecordInSitemap()
    {
        $loc = 'http://localhost/unit-blog/2000-01-01/a-b-c-lalala';
        $log = $this->sitemap->getLogByLoc($loc);
        $this->assertInstanceOf(Log::class, $log);
    }


    /**
     * record exists in sitemap, but file was removed
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function test_getLogByLoc_RecordExistsButNoLogFile()
    {
        $logToRemoveFilename = TESTDIR.'/_data/logs/2017-04-26_to_remove.txt';
        file_put_contents($logToRemoveFilename, "to remove\n\nline 1 content");
        $loc = 'http://localhost/unit-blog/2017-04-26/to-remove';

        file_put_contents($this->sitemap->getSitemapFilename(), $this->sitemap->generate());
        unlink($logToRemoveFilename);

        $this->sitemap->getLogByLoc($loc);
    }


    public function test_loadLog()
    {
        $log = $this->sitemap->loadLog($this->testLogFilename);
        $this->assertEquals('a b c', $log->title);
        $this->assertEquals("line 1\nline 2", $log->content);
        $this->assertEquals(\DateTime::createFromFormat('Y-m-d', '2000-01-01'), $log->date);
    }
}