<?php

namespace tests\App;

use App\Log;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class LogTest extends TestCase
{
    public function testInstance()
    {
        $filename = TESTDIR.'/_data/logs/0000-00-00_a-b-c.txt';
        $date = \DateTime::createFromFormat('Y-m-d', '2000-01-02');
        $log = new Log('title', 'content', $date, $filename);

        $this->assertEquals('title', $log->title);
        $this->assertEquals('content', $log->content);
        $this->assertEquals($date, $log->date);
    }
}
