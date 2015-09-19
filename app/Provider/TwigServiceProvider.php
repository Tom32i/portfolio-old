<?php

namespace Tom32i\Phpillip\Provider;

use Silex\Application;
use Silex\Provider\TwigServiceProvider as BaseTwigServiceProvider;
use Tom32i\Phpillip\EventListener\TemplateListener;
use Tom32i\Phpillip\Twig\MarkdownExtension;

/**
 * Twig integration for Silex.
 */
class TwigServiceProvider extends BaseTwigServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        parent::register($app);

        $app['twig.path'] = $app['root'] . '/src/Resources/views';
        $app['twig.loader.filesystem']->addPath($app['root'] . '/app/Resources/views', 'phpillip');
        $app['twig']->addExtension(new MarkdownExtension());
        $app['dispatcher']->addSubscriber(new TemplateListener($app['twig']));
    }
}
