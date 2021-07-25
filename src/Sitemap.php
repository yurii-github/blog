<?php

namespace App;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Sitemap
{
    protected $sitemap_filename;
    protected $request_stack;
    protected $logsDir;
    protected $logger;

    public function __construct(string $sitemap_filename, string $logsDir, RequestStack $request_stack)
    {
        $this->sitemap_filename = $sitemap_filename;
        $this->request_stack = $request_stack;
        $this->logsDir = $logsDir;
    }

    /**
     * generates sitemap based on all logs available in logs directory.
     *
     * @return string generated sitemap (http://www.sitemaps.org/protocol.html)
     */
    public function generate()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

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
                $url->loc = $this->request_stack->getCurrentRequest()->getSchemeAndHttpHost().$this->request_stack->getCurrentRequest()->getBaseUrl()."/$logDate/$logLinkTitle";
                $url->lastmod = date('Y-m-d', $fi->getMTime());
                $url->changefreq = 'never'; // always | hourly | daily | weekly | monthly | yearly | never
                $url->priority = 0.5; // 0.0 to 1.0
            }
        }

        // beautify
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }

    public function getSitemapFilename()
    {
        return $this->sitemap_filename;
    }

    public function loadLog($filename)
    {
        $raw = file_get_contents($filename);
        $raw = str_replace("\r", null, $raw); //fix windows-like
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

    public function getLogs()
    {
        if (!file_exists($this->sitemap_filename)) {
            file_put_contents($this->sitemap_filename, $this->generate());
        }

        $xml = simplexml_load_string(file_get_contents($this->sitemap_filename));
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
        if (!@$logs[$loc]) {
            throw new NotFoundHttpException("Log record was not found: $loc");
        }

        return $logs[$loc];
    }
}
