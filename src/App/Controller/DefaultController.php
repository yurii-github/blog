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
     *   }
     * )
     */
    public function logAction(Request $request,  $date, $title)
    {
        //dump(urldecode($_SERVER['REQUEST_URI']));
        //TODO: all PHP versions support
        //$route = iconv('cp1251', 'utf-8', $request->getSchemeAndHttpHost() . $request->getRequestUri());

        $route = urldecode($request->getSchemeAndHttpHost() . $request->getRequestUri());
        $log = $this->get('app.sitemap')->getLogByLoc($route);
        return $this->render('default/log.html.twig', ['log' => $log]);
    }


    /**
     * @Route("/sitemap.xml/{purge}", name="sitemap", defaults={"purge"="no"})
     */
    public function sitemapAction($purge)
    {
        $sitemap_filename = $this->getParameter('sitemap_filename');

        if (!file_exists($sitemap_filename) || $purge == 'yes') {
            $sitemap = $this->get('app.sitemap')->generate();
            file_put_contents($sitemap_filename, $sitemap);
        }

        return new BinaryFileResponse($sitemap_filename);
    }

}