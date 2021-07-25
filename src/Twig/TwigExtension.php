<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('inline', [$this, 'inlineFilter'], ['is_safe' => ['html']]),
            new TwigFilter('nl2brpad', [$this, 'nl2brWithPadFilter'], ['is_safe' => ['html']]),];
    }

    public function nl2brWithPadFilter($content, $pad_lenght = 0)
    {
        $pad = str_pad('', $pad_lenght, ' ', STR_PAD_LEFT);

        return str_replace(["\n", "\r\n"], "\n" . $pad, nl2br($content));
    }

    public function inlineFilter(array $files, $padLength = 0): string
    {
        $pad = str_pad('', $padLength, ' ', STR_PAD_LEFT);
        $generatedDate = date('Y-m-d H:i:s');
        $render = $pad . "/* embedded: $generatedDate */\n";

        foreach ($files as $file) {
            $render .= file_get_contents($file);
        }

        $render = str_replace(["\r", "\t", "\n"], ['', $pad, "\n$pad"], $render);

        return <<<STYLE
<style media="all">
$render
</style>
STYLE;
    }
}
