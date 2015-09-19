<?php

namespace Tom32i\Phpillip;

use Silex\Application as BaseApplication;
use Silex\Provider as SilexProvider;
use Tom32i\Phpillip\Provider as PhpillipProvider;

/**
 * Phpillip Application
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
        parent::__construct(array_merge($values, ['root' => __DIR__ . '/..']));

        $this->register(new SilexProvider\HttpFragmentServiceProvider());
        $this->register(new SilexProvider\UrlGeneratorServiceProvider());
        $this->register(new PhpillipProvider\ConfigurationServiceProvider());
        $this->register(new PhpillipProvider\ContentServiceProvider());
        $this->register(new PhpillipProvider\ControllerServiceProvider());
        $this->register(new PhpillipProvider\TwigServiceProvider());
        $this->register(new PhpillipProvider\InformatorServiceProvider());
    }
}
