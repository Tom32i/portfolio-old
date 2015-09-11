<?php

namespace Tom32i\Phpillip\Service;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Pygments code highlight
 */
class Pygments
{
    /**
     * File system
     *
     * @var FileSystem
     */
    private $files;

    /**
     * Temporary directory path
     *
     * @var string
     */
    private $tmp;

    /**
     * Constructor
     */
    public function __construct($tmp = null)
    {
        $this->tmp   = $tmp ?: sys_get_temp_dir();
        $this->files = new Filesystem();
    }

    /**
     * Highlight a portion of code with pygmentize
     *
     * @param string $value
     * @param string $language
     *
     * @return string
     */
    public function highlight($value, $language)
    {
        $path = sprintf('%s/pyg-%s.%s', $this->tmp, uniqid(), $this->getExtension($language));

        $this->files->dumpFile($path, $value);

        $value = $this->pygmentize($path);

        unlink($path);

        return $value;
    }

    /**
     * Run 'pygmentize' command on the given file
     *
     * @param string $path
     *
     * @return string
     */
    public function pygmentize($path)
    {
        $process = new Process(sprintf('pygmentize -f html %s', $path));

        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * Get extension for the given language
     *
     * @param string $language
     *
     * @return string
     */
    private function getExtension($language)
    {
        switch ($language) {
            case 'javascript':
                return 'js';

            default:
                return strtolower($language);
        }
    }
}
