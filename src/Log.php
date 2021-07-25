<?php

declare(strict_types=1);

namespace App;

class Log
{
    public $title;
    public $content;
    public $date;

    public function __construct(string $title, string $content, \DateTime $date)
    {
        $this->title = $title;
        $this->content = $content;
        $this->date = $date;
    }
}
