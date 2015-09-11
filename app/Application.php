<?php


namespace Tom32i\Phpillip;

use Silex\Application as BaseApplication;
use Silex\Provider as Provider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpFoundation\Request;
use Tom32i\Phpillip\Provider\ContentServiceProvider;
use Tom32i\Phpillip\Provider\ControllerServiceProvider;

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
            'environment' => $values['env'] ?: 'production',
            'debug'       => $values['debug'] ?: false,
        ]);

        $this->register(new Provider\TwigServiceProvider(), [
            'twig.path' => $this['root'] . '/src/Resources/views'
        ]);

        $this->register(new Provider\UrlGeneratorServiceProvider());
        $this->register(new ContentServiceProvider());
        $this->register(new ControllerServiceProvider());
    }

    /**
     * Run
     */
    public function run(Request $request = null)
    {
        if (!$this['debug']) {
            return $this['http_cache']->run($request);
        }

        return parent::run($request);
    }
}
