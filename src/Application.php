<?php

namespace Tom32i\Portfolio;

use Silex\Application as BaseApplication;
use Silex\Provider as Provider;
use Symfony\Component\HttpFoundation\Request;
use Tom32i\Portfolio\Controller\AppController;
use Tom32i\Portfolio\Provider\ContentRepositoryServiceProvider;

/**
 * App Kernel
 */
final class Application extends BaseApplication
{
    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        parent::__construct([
            'root'        => $values['root'],
            'environment' => $values['env'] ?: 'production',
            'debug'       => $values['debug'] ?: false,
        ]);

        $this->loadProviders();
        $this->loadRouting();
    }

    /**
     * Run
     */
    public function run(Request $request = null)
    {
        if (!$this['debug']) {
            return $this['http_cache']->run($request);
        }

        return parent::run($request);
    }

    /**
     * Load service providers
     */
    private function loadProviders()
    {
        $this->register(new Provider\TwigServiceProvider(), ['twig.path' => $this['root'] . '/src/Resources/views']);
        $this->register(new Provider\UrlGeneratorServiceProvider());
        $this->register(new ContentRepositoryServiceProvider());
    }

    /**
     * Load routes
     */
    private function loadRouting()
    {
        $this->get('/', 'Tom32i\\Portfolio\\Controller\\AppController::index')->bind('homepage');
        $this->get('/blog', 'Tom32i\\Portfolio\\Controller\\BlogController::index')->bind('blog');
        $this->get('/blog/{article}', 'Tom32i\\Portfolio\\Controller\\BlogController::article')->bind('article');
    }
}
