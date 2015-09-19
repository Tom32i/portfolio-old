<?php

namespace Tom32i\Phpillip\Config;

use Silex\Application;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Tom32i\Phpillip\Config\Definition\PhpillipConfiguration;
use Tom32i\Phpillip\Config\Loader\YamlConfigLoader;

/**
 * Configurator
 */
class Configurator
{
    /**
     * Application
     *
     * @var Application
     */
    private $app;

    /**
     * Configuration definition
     *
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * File locator
     *
     * @var FileLocator
     */
    private $locator;

    /**
     * Resolver
     *
     * @var LoaderResolver
     */
    private $resolver;

    /**
     * File loader
     *
     * @var DelegatingLoader
     */
    private $loader;

    /**
     * Constructor
     *
     * @param Application $app
     * @param array $configDirectories
     */
    public function __construct(Application $app, array $configDirectories = [])
    {
        $this->app           = $app;
        $this->locator       = new FileLocator($configDirectories);
        $this->resolver      = new LoaderResolver([new YamlConfigLoader($this->locator)]);
        $this->loader        = new DelegatingLoader($this->resolver);
        $this->processor     = new Processor();
        $this->configuration = new PhpillipConfiguration();
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        $configurationFiles = $this->locator->locate('config.yml', null, false);
        $configurations     = [];

        foreach ($configurationFiles as $path) {
            $configurations[] = $this->loader->load($path);
        }

        return $this->processor->processConfiguration($this->configuration, $configurations);
    }
}
