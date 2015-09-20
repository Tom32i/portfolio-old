<?php

namespace Tom32i\Phpillip\Model;

use RuntimeException;

/**
 * Paginator
 */
class Paginator
{
    /**
     * Pages
     *
     * @var array
     */
    protected $pages;

    /**
     * Constructor
     *
     * @param array $contents
     * @param integer $perPage
     */
    public function __construct(array $contents, $perPage = 10)
    {
        $this->pages = array_chunk($contents, $perPage);
    }

    /**
     * Get contents for the given page
     *
     * @param integer $page
     *
     * @return array
     */
    public function get($page = 1)
    {
        $index = $page - 1;

        if (!isset($this->pages[$index])) {
            throw new RuntimeException(sprintf('Invalid page %s of %s', $page, $this->count()));
        }

        return $this->pages[$index];
    }

    /**
     * Get number of pages fo the given contents
     *
     * @return integer
     */
    public function count()
    {
        return count($this->pages);
    }
}
