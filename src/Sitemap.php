<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Sitemap
{
    protected $sitemapFilename;
    protected $logsDir;
    protected $requestStack;


    public function __construct(string $sitemapFilename, string $logsDir, RequestStack $requestStack)
    {
        $this->sitemapFilename = $sitemapFilename;
        $this->logsDir = $logsDir;
        $this->requestStack = $requestStack;
    }


    public function flush($force = false)
    {
        if (file_exists($this->sitemapFilename) && !$force) {
            return;
        }

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        foreach (new \DirectoryIterator($this->logsDir) as $fi) {
            if ($fi->isFile()) {
                $logFilename = $fi->getFilename();
                $log = $this->loadLog($fi->getPathname());
                $logLinkTitle = str_replace(['(', ')', ' ', '\\', '/', ':', '*', '?', '"', '<', '>', '|', '+'], '-', $log->title);
                $logDate = $log->date->format('Y-m-d');
                $url = $xml->addChild('url');
                // attributes are used by our blog app, not needed for sitemap
                $url->addAttribute('date', $logDate);
                $url->addAttribute('title', $log->title);
                $url->addAttribute('file', $logFilename);
                $url->loc = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost().$this->requestStack->getCurrentRequest()->getBaseUrl()."/$logDate/$logLinkTitle";
                $url->lastmod = date('Y-m-d', $fi->getMTime());
                $url->changefreq = 'never'; // always | hourly | daily | weekly | monthly | yearly | never
                $url->priority = 0.5; // 0.0 to 1.0
            }
        }

        // write generated sitemap (https://www.sitemaps.org/protocol.html)
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        file_put_contents($this->sitemapFilename, $dom->saveXML());
    }


    public function getSitemapFilename(): string
    {
        return $this->sitemapFilename;
    }


    public function loadLog($filename): Log
    {
        $raw = file_get_contents($filename);
        $raw = str_replace("\r", '', $raw); //fix windows-like
        // extract title from content
        $lines = explode("\n", $raw);
        $title = $lines[0];
        unset($lines[0], $lines[1]);
        // rest save as content
        $content = implode("\n", $lines);
        // extract date from filename
        preg_match('/^\d{4}-\d{2}-\d{2}/', basename($filename), $date);
        $date = \DateTime::createFromFormat('Y-m-d', $date[0]);

        return new Log($title, $content, $date);
    }


    public function getLogs(): array
    {
        if (!file_exists($this->sitemapFilename)) {
            $this->flush();
        }

        $xml = simplexml_load_string(file_get_contents($this->sitemapFilename));
        $logs = [];

        foreach ($xml as $child) {
            $filename = $this->logsDir.'/'.$child['file'];
            if (!file_exists($filename)) {
                continue;
            }
            $log = $this->loadLog($filename);
            $logs[(string) $child->loc] = $log;
        }

        // apply DESC sorting
        uasort($logs, function ($first, $second) {
            return $first->date > $second->date ? -1 : 1;
        });

        return $logs;
    }


    public function getLogByLoc(string $loc)
    {
        $logs = $this->getLogs();
        if (empty($logs[$loc])) {
            throw new NotFoundHttpException("Log record was not found: $loc");
        }

        return $logs[$loc];
    }
}
