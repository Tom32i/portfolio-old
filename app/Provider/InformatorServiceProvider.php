<?php

namespace Tom32i\Phpillip\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Tom32i\Phpillip\Service\Informator;

/**
 * Informator Service Provider
 */
class InformatorServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['informator'] = $app->share(function ($app) {
            return new Informator($app['url_generator'], $app['config']);
        });

        $app->before([$app['informator'], 'beforeRequest']);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
