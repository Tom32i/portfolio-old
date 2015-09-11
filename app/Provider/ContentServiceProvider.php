<?php

namespace Tom32i\Phpillip\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Serializer\Encoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tom32i\Phpillip\Encoder\MarkdownDecoder;
use Tom32i\Phpillip\Encoder\YamlEncoder;
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
                    new YamlEncoder(),
                    new MarkdownDecoder(),
                ]
            );
        });

        $app['content_repository'] = $app->share(function ($app) {
            return new ContentRepository($app['serializer'], $app['root']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
