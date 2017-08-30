<?php

namespace App\Controller;

use App\Log;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="index")
     */
    public function indexAction()
    {
		$sitemap_filename = $this->getParameter('sitemap_filename');
		
        /** @var $cache \Symfony\Component\Cache\Adapter\FilesystemAdapter */
        $cache = $this->get('cache.app');
        $cachedResponse = $cache->getItem('page_index');
		$cachedSitemapMTime = $cache->getItem('sitemap_modify_time');
		$sitemapMTime = file_exists($sitemap_filename) ? filemtime($sitemap_filename) : 0;
		$isExpired = false;
				
		if($cachedSitemapMTime->isHit()) {
			$time = $cachedSitemapMTime->get();

			if($time < $sitemapMTime) {
				$time = $sitemapMTime;
				$cachedSitemapMTime->set($sitemapMTime);
				$cache->save($cachedSitemapMTime);
				$isExpired = true;
			}
		}

        if ($cachedResponse->isHit() && !$isExpired) {
            $response = $cachedResponse->get();
        } else {
            $logs = $this->get('app.sitemap')->getLogs();
            $response = $this->render('default/index.html.twig', ['logs' => $logs]);

            $cachedResponse->set($response);
            //$cachedResponse->expiresAfter(86400*2); # 48h
            $cache->save($cachedResponse);
        }

        return $response;
    }

    /**
     * @Route("/{date}/{title}", name="log",
     *   requirements={
     *     "date":"\d{4}-\d{2}-\d{2}"
     *   },
     *   options = { "utf8": true }
     * )
     */
    public function logAction(Request $request, $date, $title)
    {
        /** @var $cache \Symfony\Component\Cache\Adapter\FilesystemAdapter */
        $cache = $this->get('cache.app');
        $route = urldecode($request->getSchemeAndHttpHost().$request->getRequestUri());
        $cachedResponse = $cache->getItem('log_'.md5($route));

        if ($cachedResponse->isHit()) {
            $response = $cachedResponse->get();
			//$cachedResponse->expiresAfter(0);$cache->save($cachedResponse); // manual refresh
        } else {
            $log = $this->get('app.sitemap')->getLogByLoc($route);
            $response = $this->render('default/log.html.twig', ['log' => $log]);

            $cachedResponse->set($response);
            $cachedResponse->expiresAfter(86400); # 24h 86400
			
            $cache->save($cachedResponse);
        }

        return $response;
    }

    /**
     * @Route("/sitemap.xml/{purge}", name="sitemap", defaults={"purge"="no"})
     */
    public function sitemapAction($purge)
    {
        $sitemap_filename = $this->getParameter('sitemap_filename');

        if (!file_exists($sitemap_filename) || $purge == 'purge') {
            $sitemap = $this->get('app.sitemap')->generate();
            file_put_contents($sitemap_filename, $sitemap);

            /** @var $cache \Symfony\Component\Cache\Adapter\FilesystemAdapter */
            $cache = $this->get('cache.app');
            $cachedResponse = $cache->getItem('page_index');
            $cachedResponse->expiresAfter(0);
            $cache->save($cachedResponse);
        }

        return new BinaryFileResponse($sitemap_filename);
    }
}
