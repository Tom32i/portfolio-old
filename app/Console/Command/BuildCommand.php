<?php

namespace Tom32i\Phpillip\Console\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tom32i\Phpillip\Console\Service\ContentProvider;
use Tom32i\Phpillip\Console\Utils\Logger;
use Tom32i\Phpillip\Model\Sitemap;
use Tom32i\Phpillip\Routing\Route;
use Tom32i\Phpillip\Service\Paginator;

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
     * Host for absolute urls
     *
     * @var string
     */
    private $host;

    /**
     * Sitemap
     *
     * @var Sitemap
     */
    private $sitemap;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('portfolio:build')
            ->setDescription('Build portfolio')
            ->addArgument(
                'host',
                InputArgument::OPTIONAL,
                'What should be use as domain name for absolute url?'
            )
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
        $this->sitemap      = new Sitemap();
        $this->content      = $this->app['content_repository'];
        $this->urlGenerator = $this->app['url_generator'];
        $this->destination  = $this->app['root'] . '/dist';

        if ($this->host = $input->getArgument('host')) {
            $this->urlGenerator->getContext()->setHost($this->host);
        }
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

        $this->buildSitemap();
    }

    /**
     * Dump route content to dist file
     *
     * @param string $name
     * @param Route $route
     */
    private function dump($name, Route $route)
    {
        if (!in_array('GET', $route->getMethods())) {
            throw new Exception(sprintf('Invalid methods for route "%s".', $name), 1);
        }

        if ($route->hasContent()) {
            if ($route->isPaginated()) {
                $this->buildPaginatedRoute($name, $route);
            } else {
                $this->buildContentRoute($name, $route);
            }
        } else {
            $this->logger->log(sprintf('Building route <comment>%s</comment>', $name));
            $this->build($name, $route);
        }
    }

    /**
     * Build paginated route
     *
     * @param string $name
     * @param Route $route
     */
    private function buildPaginatedRoute($name, Route $route)
    {
        $type       = $route->getContent();
        $contents   = $this->content->listContents($type);
        $paginator  = new Paginator($contents);
        $length     = $paginator->count();

        $this->logger->log(sprintf('Building route <comment>%s</comment> for <info>%s</info> pages', $name, $length));
        $this->logger->getProgress($length);
        $this->logger->start();

        for ($i = 1; $i <= $length; $i++) {
            $this->build($name, $route, ['page' => $i]);
        }

        $this->logger->finish();
    }

    /**
     * Build content route
     *
     * @param string $name
     * @param Route $route
     */
    private function buildContentRoute($name, Route $route)
    {
        $type       = $route->getContent();
        $contents   = $this->content->listContents($type);
        $length     = count($contents);

        $this->logger->log(sprintf('Building route <comment>%s</comment> for <info>%s</info> <comment>%s(s)</comment>', $name, $length, $type));
        $this->logger->getProgress($length);
        $this->logger->start();

        foreach ($contents as $content) {
            $this->build($name, $route, [$type => $content]);
            $this->logger->advance();
        }

        $this->logger->finish();
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
        $path     = '/' . trim($route->getPath(), '/');
        $url      = $this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        $format   = $route->getDefault('_format') ?: 'html';
        $filename = $route->getFilename();
        $request  = Request::create($url, 'GET', array_merge($parameters, ['_format' => $format]));
        $response = $this->app->handle($request);

        if ($route->isOnSitemap()) {
            $this->sitemap->add($url, $response->headers->get('Last-Modified'));
        }

        foreach ($route->getDefaults() as $key => $value) {
            if (isset($parameters[$key]) && $parameters[$key] == $value) {
                $path = rtrim(preg_replace(sprintf('#{%s}/?#', $key), null, $path), '/');
            }
        }

        foreach ($parameters as $key => $value) {
            $path = str_replace(sprintf('{%s}', $key), (string) $value, $path);
        }

        $this->write($this->destination . $path, $response->getContent(), $format, $filename);

        $this->logger->log(sprintf('    Build path <comment>%s</comment>', $path));
    }

    /**
     * Build sitemap
     */
    private function buildSitemap()
    {
        $this->logger->log(sprintf('Building sitemap with <comment>%s</comment> urls.', count($this->sitemap)));

        $sitemap = $this->app['twig']->render('@phpillip/sitemap.xml.twig', ['sitemap' => $this->sitemap]);

        $this->write($this->destination, $sitemap, 'xml', 'sitemap');
    }

    /**
     * Write page to the file system
     *
     * @param string $path
     * @param string $content
     */
    private function write($path, $content, $format = 'html', $filename = 'index')
    {
        if (!$this->files->exists($path)) {
            $this->files->mkdir($path);
        }

        $this->files->dumpFile(sprintf('%s/%s.%s', rtrim($path, '/'), $filename, $format), $content);
    }
}
