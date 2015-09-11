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
}
