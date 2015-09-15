<?php

namespace Tom32i\Phpillip\Routing;

use Silex\Route as BaseRoute;

/**
 * Route
 */
class Route extends BaseRoute
{
    /**
     * Content type
     *
     * @var string
     */
    private $content;

    /**
     * Filename
     *
     * @var string
     */
    private $filename = 'index';

    /**
     * On sitemap
     *
     * @var boolean
     */
    private $onSitemap = true;

    /**
     * Content
     *
     * @param string $content
     *
     * @return Route
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Has content?
     *
     * @return boolean
     */
    public function hasContent()
    {
        return $this->content !== null;
    }

    /**
     * Paginate
     *
     * @return Route
     */
    public function paginate()
    {
        if (!$this->isPaginated()) {
            $this
                ->setPath($this->getPath() . '/{page}')
                ->value('page', 1)
                ->assert('page', '\d+');
        }

        return $this;
    }

    /**
     * Is pagination enabled?
     *
     * @return boolean
     */
    public function isPaginated()
    {
        return $this->hasDefault('page');
    }

    /**
     * Hide from sitemap
     *
     * @return Route
     */
    public function hideFromSitemap()
    {
        $this->onSitemap = false;

        return $this;
    }

    /**
     * Is route on sitemap
     *
     * @return boolean
     */
    public function isOnSitemap()
    {
        return $this->onSitemap;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return Route
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set RSS
     *
     * @return Route
     */
    public function rss()
    {
        $this
            ->setFilename('feed')
            ->hideFromSitemap()
            ->setDefault('_format', 'rss')
            ->setRequirement('_format', 'rss');

        var_dump($this);

        return $this;
    }
}
