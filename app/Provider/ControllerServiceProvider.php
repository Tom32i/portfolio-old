<?php

namespace Tom32i\Phpillip\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Controller Service Provider
 */
class ControllerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app->get('/', 'Tom32i\\Portfolio\\Controller\\AppController::index')
            ->bind('homepage');

        $app->get('/blog', 'Tom32i\\Portfolio\\Controller\\BlogController::index')
            ->bind('blog');

        $app->get('/blog/{article}', 'Tom32i\\Portfolio\\Controller\\BlogController::article')
            ->bind('article');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
