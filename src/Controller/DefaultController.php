<?php

namespace App\Controller;

use App\Sitemap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;

class DefaultController extends AbstractController
{
    public function index(Sitemap $sitemap, CacheInterface $cache)
    {
        $cachedResponse = $cache->getItem('page_index');
        $cachedSitemapMTime = $cache->getItem('sitemap_modify_time');
        $sitemapMTime = (int) @filemtime($sitemap->getSitemapFilename());
        $isExpired = false;

        if ($cachedSitemapMTime->isHit()) {
            $time = $cachedSitemapMTime->get();

            if ($time < $sitemapMTime) {
                $cachedSitemapMTime->set($sitemapMTime);
                $cache->save($cachedSitemapMTime);
                $isExpired = true;
            }
        }

        if ($cachedResponse->isHit() && !$isExpired) {
            $response = $cachedResponse->get();
        } else {
            $logs = $sitemap->getLogs();
            $response = $this->render('index.html.twig', ['logs' => $logs]);
            $cachedResponse->set($response)->expiresAfter($this->getParameter('cache_sitemap'));
            $cache->save($cachedResponse);
        }

        return $response;
    }

    public function log(Request $request, CacheInterface $cache, Sitemap $sitemap, string $date, string $title)
    {
        $route = urldecode($request->getSchemeAndHttpHost().$request->getRequestUri());
        $cachedResponse = $cache->getItem('log_'.md5($route));

        if ($cachedResponse->isHit()) {
            $response = $cachedResponse->get();
        } else {
            $log = $sitemap->getLogByLoc($route);
            $response = $this->render('log.html.twig', ['log' => $log]);

            $cachedResponse->set($response)->expiresAfter($this->getParameter('cache_log'));
            $cache->save($cachedResponse);
        }

        return $response;
    }

    public function sitemap(Sitemap $sitemap, CacheInterface $cache, $purge)
    {
        if (!file_exists($sitemap->getSitemapFilename()) || 'purge' == $purge) {
            file_put_contents($sitemap->getSitemapFilename(), $sitemap->generate());

            $cachedResponse = $cache->getItem('page_index');
            $cachedResponse->expiresAfter(0);
            $cache->save($cachedResponse);
        }

        return new BinaryFileResponse($sitemap->getSitemapFilename());
    }
}
