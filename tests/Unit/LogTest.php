<?php

namespace Tests\Unit;

use App\Log;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Log
 */
class LogTest extends TestCase
{
    public function testInstance()
    {
        $date = \DateTime::createFromFormat('Y-m-d', '2000-01-02');
        $log = new Log('title', 'content', $date);

        $this->assertEquals('title', $log->title);
        $this->assertEquals('content', $log->content);
        $this->assertEquals($date, $log->date);
    }
}
