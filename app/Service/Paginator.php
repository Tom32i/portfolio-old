<?php

namespace Tom32i\Phpillip\Service;

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
    private $pages;

    /**
     * Constructor
     *
     * @param array $contents
     * @param integer|null $perPage
     */
    public function __construct(array $contents, $perPage = null)
    {
        $this->pages = array_chunk($contents, $perPage ?: 10);
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
