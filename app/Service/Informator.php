<?php

namespace Tom32i\Phpillip\Service;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Informator
 */
class Informator
{
    /**
     * Url Generator
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * Application parameters
     *
     * @var array
     */
    private $parameters;

    /**
     * Injecting dependencies
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param array $parameters
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, array $parameters)
    {
        $this->urlGenerator = $urlGenerator;
        $this->parameters   = $parameters;
    }

    /**
     * Before request
     *
     * @param Request $request
     * @param Application $app
     */
    public function beforeRequest(Request $request, Application $app)
    {
        $url = $this->urlGenerator->generate(
            $request->attributes->get('_route'),
            $request->attributes->get('_route_params'),
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $request->attributes->set('_canonical', $url);

        $app['twig']->addGlobal('canonical', $url);
        $app['twig']->addGlobal('parameters', $this->parameters);
    }
}
