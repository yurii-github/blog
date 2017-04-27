<?php
namespace App;

class Log
{
    public $date;
    public $title;
    public $loc;
    public $content;
    public $filename;

    public function __construct($filename, $loc = null)
    {
        $this->filename = $filename;
        $this->loc = $loc;
        $raw = file_get_contents($filename);
        $raw = str_replace("\r", null, $raw); //fix windows-like
        // extract title from content
        $lines = explode("\n", $raw);
        $this->title = $lines[0];
        unset($lines[0], $lines[1]);
        // rest save as content
        $this->content = implode("\n", $lines);
        // extract date from filename
        preg_match('/\d{4}-\d{2}-\d{2}/', $filename, $date);
        $this->date = $date[0];
    }

}