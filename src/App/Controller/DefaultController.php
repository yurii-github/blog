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
        $logs = $this->get('app.sitemap')->getLogs();

        return $this->render('default/index.html.twig', ['logs' => $logs]);
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
        $cacheKey = 'log_'.md5($route);
        $cachedResponse = $cache->getItem($cacheKey);

        if ($cachedResponse->isHit()) {
            $response = $cachedResponse->get();
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
        }

        return new BinaryFileResponse($sitemap_filename);
    }
}
