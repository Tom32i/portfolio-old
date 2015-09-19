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
            ->paginate('article', 'date', false)
            ->format('html')
            ->bind('blog');

        $app->get('blog-latest', 'Tom32i\\Portfolio\\Controller\\BlogController::latest')
            ->contents('article', 'date', false)
            ->hide()
            ->bind('blog_latest');

        $app->get('/blog/feed.rss', 'Tom32i\\Portfolio\\Controller\\BlogController::feed')
            ->contents('article', 'date', false)
            ->hideFromSitemap()
            ->bind('blog_rss');

        $app->get('/blog/{article}', 'Tom32i\\Portfolio\\Controller\\BlogController::article')
            ->content('article')
            ->bind('article');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
