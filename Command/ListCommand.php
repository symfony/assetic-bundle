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

use Assetic\Asset\AssetInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Outputs a list of all asset files and optionally an md5 hash of each asset,
 * from the source file content (--md5=file) or the dumped output (--md5=dump)
 *
 * Output can be used to calculate a hash of all source files for cache busting.
 *
 * @author Ville Mattila <ville@eventio.fi>
 */
class ListCommand extends ContainerAwareCommand
{
    private $am;
    
    private $paths;
    private $md5;

    protected function configure()
    {
        $this
            ->setName('assetic:list')
            ->setDescription('Outputs a list of all asset files.')
            ->addOption('paths', null, InputOption::VALUE_NONE, 'Output full file paths.')
            ->addOption('md5', null, InputOption::VALUE_REQUIRED, 'Prepends each file with an MD5 hash of the file content. Supported values: dump, file')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->paths = $input->getOption('paths');
        $this->md5 = $input->getOption('md5');

        if ($this->md5 && false === in_array($this->md5, array('dump','file'))) {
            throw new \InvalidArgumentException('Option --md5 contains invalid value "' . $this->md5 . '". Accepted values: dump, file.');
        }

        $this->am = $this->getContainer()->get('assetic.asset_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->am->getNames() as $name) {
            $asset = $this->am->get($name);
            $this->printAsset($asset, $output);
        }
    }

    /**
     * Does the actual print for the asset and it's leafs
     *
     * @param AssetInterface  $asset  The asset, as given in execute() function
     * @param OutputInterface $output The command output
     */
    private function printAsset(AssetInterface $asset, OutputInterface $output)
    {
        
        $this->doPrint($asset, $output);
        foreach ($asset as $leaf) {
            $this->doPrint($leaf, $output);
        }
    }

    /**
     * Prints out the actual asset information: md5 sum (optionally) and (full) path
     *
     * @param AssetInterface  $asset  The asset
     * @param OutputInterface $output The command output
     */
    private function doPrint(AssetInterface $asset, OutputInterface $output)
    {
        $source = $asset->getSourcePath();
        if ($source) {
            if ($this->paths) {
                $path = $asset->getSourceRoot() . '/' . $source;
            } else {
                $path = $source;
            }

            if ($this->md5) {
                if ($this->md5 === 'dump') {
                    $md5 = md5($asset->dump());
                } elseif ($this->md5 === 'file') {
                    $md5 = md5_file($asset->getSourceRoot() . '/' . $source);
                }
                $output->writeln($md5 . ' ' . $path);
            } else {
                $output->writeln($path);
            }
        }
    }
}
