<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('inline', [$this, 'inlineFilter'], ['is_safe' => ['html']]),
            new TwigFilter('nl2brpad', [$this, 'nl2brWithPadFilter'], ['is_safe' => ['html']]), ];
    }

    public function nl2brWithPadFilter($content, $pad_lenght = 0)
    {
        $pad = str_pad('', $pad_lenght, ' ', STR_PAD_LEFT);

        return str_replace(["\n", "\r\n"], "\n".$pad, nl2br($content));
    }

    public function inlineFilter(array $files, $pad_lenght = 0)
    {
        $render = '';
        $pad = str_pad('', $pad_lenght, ' ', STR_PAD_LEFT);
        $generatedDate = date('Y-m-d H:i:s');
        $render = $pad."/* embedded: $generatedDate */\n";

        foreach ($files as $file) {
            $render .= file_get_contents($file);
        }

        //beautify
        $render = str_replace("\r", null, $render);
        $render = str_replace("\t", $pad, $render);
        $render = str_replace("\n", "\n$pad", $render);

        return "<style type=\"text/css\" media=\"all\">\n".$render.'</style>';
    }
}
