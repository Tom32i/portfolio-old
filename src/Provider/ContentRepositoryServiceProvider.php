<?php

namespace Tom32i\Portfolio\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Tom32i\Portfolio\Service\ContentRepository;

/**
 * Content Repository Service Provider
 */
class ContentRepositoryServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['content_repository'] = $app->share(function ($app) {
            return new ContentRepository($app['root']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
