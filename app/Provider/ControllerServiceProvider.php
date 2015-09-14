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

        $test = $app->get('/blog', 'Tom32i\\Portfolio\\Controller\\BlogController::index')
            ->content('article')
            ->paginate()
            ->bind('blog');

        $app->get('/blog/{article}', 'Tom32i\\Portfolio\\Controller\\BlogController::article')
            ->content('article')
            ->bind('article')
            ->convert('article', function ($article)  use ($app) {
                return $app['content_repository']->getContent('article', $article);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
