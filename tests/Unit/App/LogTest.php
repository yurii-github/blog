<?php
namespace tests\App;

use App\Log;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class LogTest extends TestCase
{

	public function testInstance()
	{
		$filename = TESTDIR.'/_data/logs/0000-00-00_a-b-c.txt';
		$log = new Log($filename);
		$this->assertEquals('a b c', $log->title);
        $this->assertEquals("line 1\nline 2", $log->content);
        $this->assertEquals('0000-00-00', $log->date);
	}

}
