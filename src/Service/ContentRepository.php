<?php

namespace Tom32i\Portfolio\Service;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Content repository
 */
class ContentRepository
{
    /**
     * Root directory
     *
     * @var string
     */
    private $root;

    /**
     * Finder
     *
     * @var Finder
     */
    private $finder;

    /**
     * Files
     *
     * @var FileSystem
     */
    private $files;

    /**
     * Constructor
     *
     * @param string $root
     * @param string $contentDir
     */
    public function __construct($root, $contentDir = 'data')
    {
        $this->root   = rtrim($root, '/') . '/' . trim($contentDir, '/');
        $this->finder = new Finder();
        $this->files  = new FileSystem();
    }

    /**
     * Get contents for the given type
     *
     * @param string $type
     *
     * @return array
     */
    public function getContents($type)
    {
        $path = sprintf('%s/%s', $this->root, $type);

        if (!$this->files->exists($path)) {
            throw new Exception(sprintf('No content directory find for type "%s".', $type), 1);
        }

        $contents = [];

        foreach ($this->finder->files()->in($path) as $file) {
            $contents[$this->getName($file)] = $this->load($file);
        }

        return $contents;
    }

    /**
     * List of content names for the given type
     *
     * @param string $type
     *
     * @return array
     */
    public function listContents($type)
    {
        $path = sprintf('%s/%s', $this->root, $type);

        if (!$this->files->exists($path)) {
            throw new Exception(sprintf('No content directory find for type "%s".', $type), 1);
        }

        $names = [];

        foreach ($this->finder->files()->in($path) as $file) {
            $names[] = $this->getName($file);
        }

        return $names;
    }

    /**
     * Get the content for the given type and name
     *
     * @param string $type
     * @param string $name
     *
     * @return array
     */
    public function getContent($type, $name)
    {
        $path = sprintf('%s/%s', $this->root, $type);

        if (!$this->files->exists($path)) {
            throw new Exception(sprintf('No content directory find for type "%s".', $type), 1);
        }

        $files = $this->finder->files()->in($path)->name($name . '.*');

        if (!$files->count()) {
            throw new Exception(sprintf('No content directory find for type "%s" and name "%s".', $type, $name), 1);
        }

        return $this->load(array_shift($files));
    }

    /**
     * Get name
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    private function getName(SplFileInfo $file)
    {
        $name = $file->getRelativePathname();

        return substr($name, 0, strrpos($name, '.'));
    }

    /**
     * Get content
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    private function load(SplFileInfo $file)
    {
        return $file->getContents();
    }
}
