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
        return $cache->get(self::CACHE_PAGE_INDEX, function (CacheItemInterface $item) use ($sitemap) {
            $logs = $sitemap->getLogs();
            return $this->render('index.html.twig', ['logs' => $logs]);
        });
    }


    public function log(Request $request, CacheInterface $cache, Sitemap $sitemap, string $date, string $title)
    {
        $route = urldecode($request->getSchemeAndHttpHost().$request->getRequestUri());
        $cacheKey = str_replace('{HASH}', md5($route), self::CACHE_PAGE_LOG);

        return $cache->get($cacheKey, function (CacheItemInterface $item) use ($sitemap, $route) {
            $log = $sitemap->getLogByLoc($route);
            return $this->render('log.html.twig', ['log' => $log]);
        });
    }


    public function sitemap(Sitemap $sitemap, CacheInterface $cache, $purge): BinaryFileResponse
    {
        if (!$sitemap->exists() || 'purge' == $purge) {
            $sitemap->flush(true);
            $cache->delete(self::CACHE_PAGE_INDEX);
        }

        return new BinaryFileResponse($sitemap->getFilename());
    }
}
