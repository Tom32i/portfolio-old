<?php

namespace Tom32i\Phpillip\Service;

use DateTime;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Tom32i\Phpillip\Behavior\PropertyHandlerInterface;

/**
 * Content repository
 */
class ContentRepository
{
    /**
     * Decoder
     *
     * @var DecoderInterface
     */
    private $decoder;

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
     * Property handlers
     *
     * @var array
     */
    private $handlers;

    /**
     * Root directory
     *
     * @var string
     */
    private $root;

    /**
     * Cache
     *
     * @var array
     */
    private $cache;

    /**
     * Constructor
     *
     * @param DecoderInterface $decoder
     * @param string $root
     * @param string $contentDir
     */
    public function __construct(DecoderInterface $decoder, $root, $contentDir = 'data')
    {
        $this->decoder  = $decoder;
        $this->root     = rtrim($root, '/') . '/' . trim($contentDir, '/');
        $this->files    = new FileSystem();
        $this->handlers = [];
        $this->cache    = [
            'files'    => [],
            'contents' => [],
        ];
    }

    /**
     * Get contents for the given type
     *
     * @param string $type Type of content to load
     * @param string $index Index the results by the given field name
     * @param string $order Sort content: true for ascending, false for descending
     *
     * @return array
     */
    public function getContents($type, $index = null, $order = true)
    {
        $contents = [];
        $files    = $this->listFiles($type);

        foreach ($files as $file) {
            $content = $this->load($file);
            $contents[$this->getIndex($file, $content, $index)] = $content;
        }

        if ($order !== null) {
            $order ? ksort($contents) : krsort($contents);
        }

        return $contents;
    }

    /**
     * List of content names for the given type
     *
     * @param string $type Type of content to list
     *
     * @return array
     */
    public function listContents($type)
    {
        $names = [];
        $files = $this->listFiles($type);

        foreach ($files as $file) {
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
        $finder = $this->listFiles($type)->name($name . '.*');

        if (!$finder->count()) {
            throw new Exception(sprintf('No content directory find for type "%s" and name "%s".', $type, $name), 1);
        }

        foreach ($finder as $file) {
            return $this->load($file);
        }

        return null;
    }

    /**
     * Add property handler
     *
     * @param PropertyHandlerInterface $handler
     */
    public function addPropertyHandler(PropertyHandlerInterface $handler)
    {
        $this->handlers[$handler->getProperty()] = $handler;
    }

    /**
     * Get name
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    public static function getName(SplFileInfo $file)
    {
        $name = $file->getRelativePathname();

        return substr($name, 0, strrpos($name, '.'));
    }

    /**
     * List files
     *
     * @param string $type
     *
     * @return Finder
     */
    private function listFiles($type)
    {
        if (!isset($this->cache['files'][$type])) {
            $path = sprintf('%s/%s', $this->root, $type);

            if (!$this->files->exists($path)) {
                throw new Exception(sprintf('No content directory find for type "%s".', $type), 1);
            }

            $finder = new Finder();

            $this->cache['files'][$type] = $finder->files()->in($path);
        }

        return clone $this->cache['files'][$type];
    }

    /**
     * Get format
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    private function getFormat(SplFileInfo $file)
    {
        $name = $file->getRelativePathname();
        $ext  = substr($name, strrpos($name, '.') + 1);

        switch ($ext) {
            case 'md':
                return 'markdown';

            case 'yml':
                return 'yaml';

            default:
                return $ext;
        }
    }

    /**
     * Get index for the given content
     *
     * @param SplFileInfo $file
     * @param array $content
     * @param string|null $key
     *
     * @return string
     */
    private function getIndex(SplFileInfo $file, $content, $key)
    {
        if (!$key || !isset($content[$key])) {
            return $this->getName($file);
        }

        $index = $content[$key];

        if ($index instanceof DateTime) {
            return $index->format('U');
        }

        return (string) $index;
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
        $path = $file->getPathName();

        if (!isset($this->cache['contents'][$path])) {
            $data    = $this->decoder->decode($file->getContents(), $this->getFormat($file));
            $context = ['file' => $file];

            foreach ($this->handlers as $property => $handler) {
                if ($handler->isSupported($data)) {
                    $data[$property] = $handler->handle(isset($data[$property]) ? $data[$property] : null, $context);
                }
            }

            $this->cache['contents'][$path] = $data;
        }

        return $this->cache['contents'][$path];
    }
}
