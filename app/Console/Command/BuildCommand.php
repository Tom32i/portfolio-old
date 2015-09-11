<?php


namespace Tom32i\Phpillip\Console\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Tom32i\Phpillip\Console\Service\ContentProvider;
use Tom32i\Phpillip\Console\Utils\Logger;

/**
 * Build Command
 */
class BuildCommand extends Command
{
    /**
     * Application
     *
     * @var Symfony\Component\Console\Application
     */
    private $app;

    /**
     * File system
     *
     * @var FileSystem
     */
    private $files;

    /**
     * Destination folder
     *
     * @var string
     */
    private $destination;

    /**
     * Logger
     *
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('portfolio:build')
            ->setDescription('Build portfolio')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->app          = $this->getApplication()->getKernel();
        $this->files        = new Filesystem();
        $this->logger       = new Logger($output);
        $this->content      = $this->app['content_repository'];
        $this->urlGenerator = $this->app['url_generator'];
        $this->destination  = $this->app['root'] . '/dist';
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->log(sprintf('Building <info>%s</info> routes...', $this->app['routes']->count()));

        foreach ($this->app['routes'] as $name => $route) {
            $this->dump($name, $route);
        }
    }

    /**
     * Dump route content to dist file
     *
     * @param Route $route
     */
    private function dump($name, Route $route)
    {
        if (!in_array('GET', $route->getMethods())) {
            throw new Exception(sprintf('Invalid methods for route "%s".', $name), 1);
        }

        $parameters = $this->getParameters($name, $route);

        if (empty($parameters)) {
            $this->logger->log(sprintf('Building route <comment>%s</comment>', $name));
            $this->build($name, $route);
        } else {
            $this->logger->log(sprintf(
                'Building route <comment>%s</comment> for <info>%s</info> <comment>%s(s)</comment>',
                $name,
                count(array_values($parameters)[0]),
                array_keys($parameters)[0]
            ));

            $this->logger->getProgress(count($parameters));
            $this->logger->start();

            foreach ($parameters as $variable => $contents) {
                foreach ($contents as $content) {
                    $this->build($name, $route, [$variable => $content]);
                    $this->logger->advance();
                }
            }

            $this->logger->finish();
        }
    }

    /**
     * Build the given route for the given parameters
     *
     * @param string $name
     * @param Route $route
     * @param array $parameters
     */
    private function build($name, Route $route, array $parameters = [])
    {
        $content = $this->call($name, $route, $parameters);
        $path    = '/' . trim($route->getPath(), '/');

        foreach ($parameters as $key => $value) {
            $path = str_replace(sprintf('{%s}', $key), (string) $value, $path);
        }

        $this->write($this->destination . $path, $content);

        $this->logger->log(sprintf('    Build path <comment>%s</comment>', $path));
    }

    /**
     * Get parameters for the given route
     *
     * @param string $name
     * @param Route $route
     *
     * @return array
     */
    private function getParameters($name, Route $route)
    {
        $variables = $route->compile()->getVariables();

        switch (count($variables)) {
            case 0:
                return [];

            case 1:
                $variable = array_shift($variables);

                return [$variable => $this->content->listContents($variable)];

            default:
                throw new Exception(sprintf(
                    'Only one url variable accepted, %s provided for route "%s".',
                    count($variables),
                    $name
                ), 1);
        }
    }

    /**
     * Call a route and return its response content
     *
     * @param Route $route
     *
     * @return string
     */
    private function call($name, Route $route, array $parameters = [])
    {
        $url      = $this->urlGenerator->generate($name, $parameters);
        $request  = Request::create($url, 'GET', $parameters);
        $response = $this->app->handle($request);

        return $response->getContent();
    }

    /**
     * Write page to the file system
     *
     * @param string $path
     * @param string $content
     */
    private function write($path, $content, $filename = 'index.html')
    {
        if (!$this->files->exists($path)) {
            $this->files->mkdir($path);
        }

        $this->files->dumpFile(rtrim($path, '/') . '/' . $filename, $content);
    }
}
