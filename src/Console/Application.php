<?php

namespace Tom32i\Portfolio\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Application
 */
class Application extends BaseApplication
{
    /**
     * Kernel
     *
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * Constructor.
     *
     * @param HttpKernelInterface $kernel A HttpKernelInterface instance
     */
    public function __construct(HttpKernelInterface $kernel)
    {
        $this->kernel = $kernel;

        parent::__construct(
            'Tom32i Portfolio',
            sprintf(
                'silex-%s-%s%s',
                $kernel::VERSION,
                $kernel['environment'],
                $kernel['debug'] ? '/debug' : ''
            )
        );

        $this->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $kernel['environment']));
        $this->getDefinition()->addOption(new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'));
    }

    /**
     * Gets the Kernel associated with this Console.
     *
     * @return KernelInterface A KernelInterface instance
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->kernel->boot();
        $this->kernel->flush();

        return parent::doRun($input, $output);
    }
}
