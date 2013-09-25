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

use Assetic\Util\VarUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps assets as their source files are modified.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class WatchCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('assetic:watch')
            ->setDescription('Dumps assets to the filesystem as their source files are modified')
            ->addArgument('write_to', InputArgument::OPTIONAL, 'Override the configured asset root')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force an initial generation of all assets')
            ->addOption('period', null, InputOption::VALUE_REQUIRED, 'Set the polling period in seconds', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $stdout)
    {
        // capture error output
        $stderr = $stdout instanceof ConsoleOutputInterface
            ? $stdout->getErrorOutput()
            : $stdout;

        // print the header
        $stdout->writeln(sprintf('Dumping all <comment>%s</comment> assets.', $input->getOption('env')));
        $stdout->writeln(sprintf('Debug mode is <comment>%s</comment>.', $this->am->isDebug() ? 'on' : 'off'));
        $stdout->writeln('');

        // establish a temporary status file
        $cache = sys_get_temp_dir().'/assetic_watch_'.substr(sha1($this->basePath), 0, 7);
        if ($input->getOption('force') || !file_exists($cache)) {
            $previously = array();
        } else {
            $previously = unserialize(file_get_contents($cache));
            if (!is_array($previously)) {
                $previously = array();
            }
        }

        $error = '';
        while (true) {
            try {
                foreach ($this->am->getNames() as $name) {
                    if ($this->checkAsset($name, $previously)) {
                        $this->dumpAsset($name, $stdout, $previously);
                    }
                }

                // reset the asset manager
                $this->am->clear();
                $this->am->load();

                file_put_contents($cache, serialize($previously));
                $error = '';
            } catch (\Exception $e) {
                if ($error != $msg = $e->getMessage()) {
                    $stderr->writeln('<error>[error]</error> '.$msg);
                    $error = $msg;
                }
            }

            clearstatcache ();
            sleep($input->getOption('period'));
        }
    }
}
