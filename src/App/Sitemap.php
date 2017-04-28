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
     * generates sitemap based on all logs available in logs directory
     *
     * @return string generated sitemap (http://www.sitemaps.org/protocol.html)
     */
    public function generate()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        foreach (new \DirectoryIterator($this->logsDir) as $fi) {
            if ($fi->isFile()) {
                $log_filename = $fi->getFilename();
                preg_match('/^\d{4}-\d{2}-\d{2}/', $log_filename, $date);
                $date = $date[0];
                $log = file_get_contents($fi->getPathname());
                $log = str_replace("\r", '', $log);
                $title = substr($log, 0, stripos($log, "\n"));
                $link_title = str_replace(['(',')',' ','\\','/',':','*','?','"','<','>','|','+'], '-', $title);

                $url = $xml->addChild('url');
                // atributes are used by our blog app, not needed for sitemap
                $url->addAttribute('date', $date);
                $url->addAttribute('title', $title);
                //$url->addAttribute('file', iconv(config::get()->get_settings('fs_encoding'), 'utf-8', $log_filename));
                $url->addAttribute('file', $log_filename);
                $url->loc = $this->request_stack->getCurrentRequest()->getSchemeAndHttpHost().$this->request_stack->getCurrentRequest()->getBaseUrl() . "/$date/$link_title";
                $url->lastmod = date('Y-m-d', $fi->getMTime());
                $url->changefreq = 'never'; // always hourly daily weekly monthly yearly never
                $url->priority = 0.5; // 0.0 to 1.0
            }
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    public function getSitemapFilename()
    {
        return $this->sitemap_filename;
    }

    public function getLogs()
    {
        if (!file_exists($this->sitemap_filename)) {
            file_put_contents($this->sitemap_filename, $this->generate());
        }

        //TODO: add APCu cache
        $xml = simplexml_load_string(file_get_contents($this->sitemap_filename));
        $logs = [];

        foreach ($xml as $child) {
            $filename = $this->logsDir.'/'.$child['file'];
            if (!file_exists($filename)) {
                continue;
            }
            $log = new Log($filename, (string)$child->loc);
            $logs[$log->loc] = $log;
        }

        // apply DESC sorting
        uasort($logs, function ($first, $second) {
            return $first->date > $second->date ? - 1 : 1;
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
