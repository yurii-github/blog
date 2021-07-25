<?php

declare(strict_types=1);

namespace App\Controller;

use App\Sitemap;
use Psr\Cache\CacheItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;

class DefaultController extends AbstractController
{
    protected const CACHE_PAGE_INDEX = 'page_index';
    protected const CACHE_PAGE_LOG = 'page_log_{HASH}';

    public function index(Sitemap $sitemap, CacheInterface $cache)
    {
        $cachedResponse = $cache->getItem(self::CACHE_PAGE_INDEX);
        assert($cachedResponse instanceof CacheItemInterface);

        if (!$cachedResponse->isHit()) {
            $logs = $sitemap->getLogs();
            $response = $this->render('index.html.twig', ['logs' => $logs]);
            $cachedResponse->set($response);
            $cachedResponse->expiresAfter($this->getParameter('cache_sitemap'));
            $cache->save($cachedResponse);
            $sitemap->flush();
        }

        return $cachedResponse->get();
    }


    public function log(Request $request, CacheInterface $cache, Sitemap $sitemap, string $date, string $title)
    {
        $route = urldecode($request->getSchemeAndHttpHost().$request->getRequestUri());
        $cachedResponse = $cache->getItem(str_replace('{HASH}', md5($route), self::CACHE_PAGE_LOG));
        assert($cachedResponse instanceof CacheItemInterface);

        if (!$cachedResponse->isHit()) {
            $log = $sitemap->getLogByLoc($route);
            $response = $this->render('log.html.twig', ['log' => $log]);
            $cachedResponse->set($response);
            $cachedResponse->expiresAfter($this->getParameter('cache_log'));
            $cache->save($cachedResponse);
        }

        return $cachedResponse->get();
    }


    public function sitemap(Sitemap $sitemap, CacheInterface $cache, $purge): BinaryFileResponse
    {
        if (!file_exists($sitemap->getSitemapFilename()) || 'purge' == $purge) {
            $sitemap->flush();
            $cache->delete(self::CACHE_PAGE_INDEX);
        }

        return new BinaryFileResponse($sitemap->getSitemapFilename());
    }
}
