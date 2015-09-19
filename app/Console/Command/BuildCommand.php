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
use Tom32i\Phpillip\Model\Paginator;
use Tom32i\Phpillip\Model\Sitemap;
use Tom32i\Phpillip\Routing\Route;

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
        $this->content      = $this->app['content_repository'];
        $this->urlGenerator = $this->app['url_generator'];
        $this->destination  = $this->app['root'] . '/dist';

        if ($this->app['sitemap']) {
            $this->sitemap = new Sitemap();
        }

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
            if ($route->isVisible()) {
                $this->dump($name, $route);
            }
        }

        if ($this->sitemap) {
            $this->buildSitemap();
        }
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
            if ($route->isList()) {
                if ($route->isPaginated()) {
                    $this->buildPaginatedRoute($name, $route);
                } else {
                    $this->buildListRoute($name, $route);
                }
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
     * Build list route
     *
     * @param string $name
     * @param Route $route
     */
    private function buildListRoute($name, Route $route)
    {
        $type     = $route->getContent();
        $contents = $this->content->listContents($type);

        $this->logger->log(sprintf('Building route <comment>%s</comment> with <info>%s</info> <comment>%s(s)</comment>', $name, count($contents), $type));
        $this->build($name, $route);
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
        $url      = $this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        $format   = $route->getDefault('_format') ?: 'html';
        $filepath = trim($route->getFilePath(), '/');
        $filename = $route->getFileName();
        $request  = Request::create($url, 'GET', array_merge($parameters, ['_format' => $format]));
        $response = $this->app->handle($request);

        if ($this->sitemap && $route->isMapped()) {
            $this->sitemap->add($url, $response->headers->get('Last-Modified'));
        }

        foreach ($route->getDefaults() as $key => $value) {
            if (isset($parameters[$key]) && $parameters[$key] == $value) {
                $filepath = rtrim(preg_replace(sprintf('#{%s}/?#', $key), null, $filepath), '/');
            }
        }

        foreach ($parameters as $key => $value) {
            $filepath = str_replace(sprintf('{%s}', $key), (string) $value, $filepath);
        }

        $this->write($filepath, $response->getContent(), $format, $filename);
    }

    /**
     * Build sitemap
     */
    private function buildSitemap()
    {
        $this->logger->log(sprintf('Building sitemap with <comment>%s</comment> urls.', count($this->sitemap)));

        $sitemap = $this->app['twig']->render('@phpillip/sitemap.xml.twig', ['sitemap' => $this->sitemap]);

        $this->write('/', $sitemap, 'xml', 'sitemap');
    }

    /**
     * Write page to the file system
     *
     * @param string $path
     * @param string $content
     * @param string $filename
     * @param string $format
     */
    private function write($path, $content, $format = 'html', $filename = 'index')
    {
        $directory = sprintf('%s/%s', $this->destination, trim($path, '/'));
        $file      = sprintf('%s.%s', $filename, $format);

        if (!$this->files->exists($directory)) {
            $this->files->mkdir($directory);
        }

        $this->files->dumpFile(sprintf('%s/%s', $directory, $file), $content);
        $this->logger->log(sprintf('    Built file <comment>%s/</comment><info>%s</info>', trim($path, '/'), $file));
    }
}
