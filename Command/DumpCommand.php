<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Command;

use Spork\Batch\Strategy\ChunkStrategy;
use Spork\EventDispatcher\WrappedEventDispatcher;
use Spork\ProcessManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps assets to the filesystem.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class DumpCommand extends AbstractCommand
{
    private $spork;

    protected function configure()
    {
        $this
            ->setName('assetic:dump')
            ->setDescription('Dumps all assets to the filesystem')
            ->addArgument('write_to', InputArgument::OPTIONAL, 'Override the configured asset root')
            ->addOption('forks', null, InputOption::VALUE_REQUIRED, 'Fork work across many processes (requires kriswallsmith/spork)')
            ->addOption('watch', null, InputOption::VALUE_NONE, 'DEPRECATED: use assetic:watch instead')
            ->addOption('force', null, InputOption::VALUE_NONE, 'DEPRECATED: use assetic:watch instead')
            ->addOption('period', null, InputOption::VALUE_REQUIRED, 'DEPRECATED: use assetic:watch instead', 1)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (null !== $input->getOption('forks')) {
            if (!class_exists('Spork\ProcessManager')) {
                throw new \RuntimeException('The --forks option requires that package kriswallsmith/spork be installed');
            }

            if (!is_numeric($input->getOption('forks'))) {
                throw new \InvalidArgumentException('The --forks options must be numeric');
            }

            $this->spork = new ProcessManager(
                new WrappedEventDispatcher($this->getContainer()->get('event_dispatcher')),
                null,
                $this->getContainer()->getParameter('kernel.debug')
            );
        }

        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('watch')) {
            $this->io->caution('The --watch option is deprecated. Please use the assetic:watch command instead.');

            // build assetic:watch arguments
            $arguments = array(
                'command'  => 'assetic:watch',
                'write_to' => $this->basePath,
                '--period' => $input->getOption('period'),
                '--env'    => $input->getOption('env'),
            );

            if ($input->getOption('no-debug')) {
                $arguments['--no-debug'] = true;
            }

            if ($input->getOption('force')) {
                $arguments['--force'] = true;
            }

            $command = $this->getApplication()->find('assetic:watch');

            return $command->run(new ArrayInput($arguments), $stdout);
        }

        // print the header
        $this->io->comment(sprintf('Dumping all <comment>%s</comment> assets.', $input->getOption('env')));
        $this->io->comment(sprintf('Debug mode is <comment>%s</comment>.', $this->am->isDebug() ? 'on' : 'off'));
        $this->io->newLine();

        if ($this->spork) {
            $batch = $this->spork->createBatchJob(
                $this->am->getNames(),
                new ChunkStrategy($input->getOption('forks'))
            );

            $self = $this;
            $batch->execute(function ($name) use ($self, $output) {
                $self->dumpAsset($name, $stdout);
            });
        } else {
            foreach ($this->am->getNames() as $name) {
                $this->dumpAsset($name, $output);
            }
        }
    }
}
