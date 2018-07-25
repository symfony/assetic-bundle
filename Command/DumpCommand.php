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

    protected function execute(InputInterface $input, OutputInterface $stdout)
    {
        // capture error output
        $stderr = $stdout instanceof ConsoleOutputInterface
            ? $stdout->getErrorOutput()
            : $stdout;

        if ($input->getOption('watch')) {
            $stderr->writeln(
                '<error>The --watch option is deprecated. Please use the '.
                'assetic:watch command instead.</error>'
            );

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
        $stdout->writeln(sprintf('Dumping all <comment>%s</comment> assets.', $input->getOption('env')));
        $stdout->writeln(sprintf('Debug mode is <comment>%s</comment>.', $this->am->isDebug() ? 'on' : 'off'));
        $stdout->writeln('');

        $this->dumpAssets($this->am->getNames(), $stdout, $input->getOption('forks'));
    }
}
