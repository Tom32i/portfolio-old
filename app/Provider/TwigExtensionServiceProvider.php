<?php

namespace Tom32i\Phpillip\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Tom32i\Phpillip\Twig\MarkdownExtension;

/**
 * TwigExtension Service Provider
 */
class TwigExtensionServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $app['twig']->addExtension(new MarkdownExtension());
    }
}
