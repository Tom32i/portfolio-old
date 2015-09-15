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
            ->content('article')
            ->paginate()
            ->setDefault('_format', 'html')
            ->setRequirement('_format', 'html')
            ->bind('blog');

        $app->get('/blog/{article}', 'Tom32i\\Portfolio\\Controller\\BlogController::article')
            ->content('article')
            ->bind('article')
            ->convert('article', function ($article)  use ($app) {
                return $app['content_repository']->getContent('article', $article);
            });

        $app->get('/blog/', 'Tom32i\\Portfolio\\Controller\\BlogController::rss')
            ->content('article')
            ->rss()
            ->bind('blog_rss');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
