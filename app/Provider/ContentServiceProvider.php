<?php

namespace Tom32i\Phpillip\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Serializer\Encoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tom32i\Phpillip\Encoder as PhpillipEncoder;
use Tom32i\Phpillip\Encoder\YamlEncoder;
use Tom32i\Phpillip\EventListener;
use Tom32i\Phpillip\PropertyHandler;
use Tom32i\Phpillip\Service\ContentRepository;

/**
 * Content Service Provider
 */
class ContentServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['serializer'] = $app->share(function ($app) {
            return new Serializer(
                [],
                [
                    new Encoder\XmlEncoder(),
                    new Encoder\JsonEncoder(),
                    new PhpillipEncoder\YamlEncoder(),
                    new PhpillipEncoder\MarkdownDecoder(),
                ]
            );
        });

        $app['content_repository'] = $app->share(function ($app) {
            return new ContentRepository($app['serializer'], $app['root']);
        });

        $app['dispatcher']->addSubscriber(new EventListener\ContentConverterListener($app['routes'], $app['content_repository']));
        $app['dispatcher']->addSubscriber(new EventListener\LastModifierListener($app['routes']));

        $app['content_repository']->addPropertyHandler(new PropertyHandler\DateTimePropertyHandler());
        $app['content_repository']->addPropertyHandler(new PropertyHandler\IntegerPropertyHandler('weight'));
        $app['content_repository']->addPropertyHandler(new PropertyHandler\LastModifiedPropertyHandler());
        $app['content_repository']->addPropertyHandler(new PropertyHandler\SlugPropertyHandler());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
