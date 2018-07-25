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

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Util\VarUtils;
use Spork\Batch\Strategy\ChunkStrategy;
use Spork\EventDispatcher\WrappedEventDispatcher;
use Spork\ProcessManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends ContainerAwareCommand
{
    protected $am;
    protected $basePath;
    /** @var ProcessManager */
    protected $spork;

    protected function initialize(InputInterface $input, OutputInterface $stdout)
    {
        $this->am = $this->getContainer()->get('assetic.asset_manager');

        $this->basePath = $this->getContainer()->getParameter('assetic.write_to');
        if ($input->hasArgument('write_to') && $basePath = $input->getArgument('write_to')) {
            $this->basePath = $basePath;
        }

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
    }

    /**
     * Writes an asset.
     *
     * If the application or asset is in debug mode, each leaf asset will be
     * dumped as well.
     *
     * @param string          $name   An asset name
     * @param OutputInterface $stdout The command output
     */
    public function dumpAsset($name, OutputInterface $stdout)
    {
        $asset = $this->am->get($name);
        $formula = $this->am->hasFormula($name) ? $this->am->getFormula($name) : array();

        // start by dumping the main asset
        $this->doDump($asset, $stdout);

        $debug = isset($formula[2]['debug']) ? $formula[2]['debug'] : $this->am->isDebug();
        $combine = isset($formula[2]['combine']) ? $formula[2]['combine'] : !$debug;

        // dump each leaf if no combine
        if (!$combine) {
            foreach ($asset as $leaf) {
                $this->doDump($leaf, $stdout);
            }
        }
    }

    /**
     * Performs the asset dump.
     *
     * @param AssetInterface  $asset  An asset
     * @param OutputInterface $stdout The command output
     *
     * @throws RuntimeException If there is a problem writing the asset
     */
    private function doDump(AssetInterface $asset, OutputInterface $stdout)
    {
        $combinations = VarUtils::getCombinations(
            $asset->getVars(),
            $this->getContainer()->getParameter('assetic.variables')
        );

        foreach ($combinations as $combination) {
            $asset->setValues($combination);

            // resolve the target path
            $target = rtrim($this->basePath, '/').'/'.$asset->getTargetPath();
            $target = str_replace('_controller/', '', $target);
            $target = VarUtils::resolve($target, $asset->getVars(), $asset->getValues());

            if (!is_dir($dir = dirname($target))) {
                $stdout->writeln(sprintf(
                    '<comment>%s</comment> <info>[dir+]</info> %s',
                    date('H:i:s'),
                    $dir
                ));

                if (false === @mkdir($dir, 0777, true)) {
                    throw new \RuntimeException('Unable to create directory '.$dir);
                }
            }

            $stdout->writeln(sprintf(
                '<comment>%s</comment> <info>[file+]</info> %s',
                date('H:i:s'),
                $target
            ));

            if (OutputInterface::VERBOSITY_VERBOSE <= $stdout->getVerbosity()) {
                if ($asset instanceof AssetCollectionInterface) {
                    foreach ($asset as $leaf) {
                        $root = $leaf->getSourceRoot();
                        $path = $leaf->getSourcePath();
                        $stdout->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
                    }
                } else {
                    $root = $asset->getSourceRoot();
                    $path = $asset->getSourcePath();
                    $stdout->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
                }
            }

            if (false === @file_put_contents($target, $asset->dump())) {
                throw new \RuntimeException('Unable to write file '.$target);
            }
        }
    }

    protected function dumpAssets($names, OutputInterface $stdout, $forks)
    {
        if ($this->spork && $forks) {
            $batch = $this->spork->createBatchJob(
                $names,
                new ChunkStrategy($forks)
            );

            $self = $this;
            $batch->execute(function ($name) use ($self, $stdout) {
                $self->dumpAsset($name, $stdout);
            });
            $this->spork->wait();
        } else {
            foreach ($names as $name) {
                $this->dumpAsset($name, $stdout);
            }
        }
    }
}
