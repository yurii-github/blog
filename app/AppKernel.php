<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../vendor/autoload.php';
// auto-load annotations
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
        ];

        if ($this->getEnvironment() == 'dev') {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
        }

        return $bundles;
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');

        if ($this->getEnvironment() == 'test') {
            $loader->load(__DIR__.'/config/config_test.yml');
        }

        // configure WebProfilerBundle only if the bundle is enabled
        if (isset($this->bundles['WebProfilerBundle'])) {
            $c->loadFromExtension('web_profiler', [
                'toolbar' => true,
                'intercept_redirects' => false,
            ]);
        }
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        // import the WebProfilerRoutes, only if the bundle is enabled
        if (isset($this->bundles['WebProfilerBundle'])) {
            $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml', '/_wdt');
            $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml', '/_profiler');
        }

        // load the annotation routes
        $routes->import(__DIR__.'/../src/App/Controller/', '/', 'annotation');
    }

    /**
     * optional, to use the standard Symfony cache directory.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return __DIR__.'/../var/cache/'.$this->getEnvironment();
    }

    /**
     * optional, to use the standard Symfony logs directory.
     *
     * @return string
     */
    public function getLogDir()
    {
        if ($this->getEnvironment() == 'test') {
            return __DIR__.'/../tests/_data/logs';
        }

        return __DIR__.'/../var/logs';
    }
}