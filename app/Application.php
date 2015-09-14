<?php

namespace Tom32i\Phpillip;

use DerAlex\Silex\YamlConfigServiceProvider;
use Silex\Application as BaseApplication;
use Silex\Provider as SilexProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpFoundation\Request;
use Tom32i\Phpillip\Provider as PhpillipProvider;

/**
 * App Kernel
 */
final class Application extends BaseApplication
{
    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        parent::__construct([
            'root'        => $values['root'],
            'environment' => $values['env'] ?: 'dev',
            'debug'       => $values['debug'] ?: false,
            'route_class' => 'Tom32i\Phpillip\Routing\Route',
        ]);

        $this->register(new YamlConfigServiceProvider($this['root'] . '/app/Resources/config/config.yml'));
        $this->register(new SilexProvider\TwigServiceProvider(), ['twig.path' => $this['root'] . '/src/Resources/views']);
        $this->register(new SilexProvider\UrlGeneratorServiceProvider());
        $this->register(new PhpillipProvider\ContentServiceProvider());
        $this->register(new PhpillipProvider\ControllerServiceProvider());
        $this->register(new PhpillipProvider\TwigExtensionServiceProvider());
        $this->register(new PhpillipProvider\InformatorServiceProvider());
        //$this->register(new MetaServiceProvider());

        # http://silex.sensiolabs.org/doc/usage.html#error-handlers

        #request_context

        #https://github.com/silexphp/Silex/wiki/Third-Party-ServiceProviders#config
        #https://github.com/silexphp/Silex/wiki/Third-Party-ServiceProviders#text-formatting
    }
}
