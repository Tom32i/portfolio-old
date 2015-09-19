<?php

namespace Tom32i\Phpillip\Provider;

use Exception;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Tom32i\Phpillip\Config\Configurator;

/**
 * Configuration service provider
 */
class ConfigurationServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $configurator = new Configurator($app, [$app['root'] . '/src/Resources/config']);

        foreach ($configurator->getConfiguration() as $key => $value) {
            $app[$key] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
