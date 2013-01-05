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

use Assetic\Util\PathUtils;

use Assetic\AssetWriter;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\LazyAssetManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps assets to the filesystem.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class DumpCommand extends ContainerAwareCommand
{
    private $basePath;
    private $verbose;
    private $am;

    protected function configure()
    {
        $this
            ->setName('assetic:dump')
            ->setDescription('Dumps all assets to the filesystem')
            ->addArgument('write_to', InputArgument::OPTIONAL, 'Override the configured asset root')
            ->addOption('watch', null, InputOption::VALUE_NONE, 'Check for changes every second, debug mode only')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force an initial generation of all assets (used with --watch)')
            ->addOption('period', null, InputOption::VALUE_REQUIRED, 'Set the polling period in seconds (used with --watch)', 1)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->basePath = $input->getArgument('write_to') ?: $this->getContainer()->getParameter('assetic.write_to');
        $this->verbose = $input->getOption('verbose');
        $this->am = $this->getContainer()->get('assetic.asset_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('Dumping all <comment>%s</comment> assets.', $input->getOption('env')));
        $output->writeln(sprintf('Debug mode is <comment>%s</comment>.', $this->am->isDebug() ? 'on' : 'off'));
        $output->writeln(sprintf('Using cache: <comment>%s</comment>.', ($this->getContainer()->getParameter('assetic.cache'))?"true":"false"));
        if($this->getContainer()->getParameter('assetic.cache')){
            $output->writeln(sprintf('Cache dir: <comment>%s</comment>.', $this->getContainer()->getParameter('assetic.cache_dir')));
        }
        $output->writeln('');

        if (!$input->getOption('watch')) {
            foreach ($this->am->getNames() as $name) {
                $this->dumpAsset($name, $output);
            }

            return;
        }

        if (!$this->am->isDebug()) {
            throw new \RuntimeException('The --watch option is only available in debug mode.');
        }

        $this->watch($input, $output);
    }

    /**
     * Watches a asset manager for changes.
     *
     * This method includes an infinite loop the continuously polls the asset
     * manager for changes.
     *
     * @param InputInterface  $input  The command input
     * @param OutputInterface $output The command output
     */
    private function watch(InputInterface $input, OutputInterface $output)
    {
        $refl = new \ReflectionClass('Assetic\\AssetManager');
        $prop = $refl->getProperty('assets');
        $prop->setAccessible(true);

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
                        $this->dumpAsset($name, $output);
                    }
                }

                // reset the asset manager
                $prop->setValue($this->am, array());
                $this->am->load();

                file_put_contents($cache, serialize($previously));
                $error = '';
            } catch (\Exception $e) {
                if ($error != $msg = $e->getMessage()) {
                    $output->writeln('<error>[error]</error> '.$msg);
                    $error = $msg;
                }
            }
            sleep($input->getOption('period'));
        }
    }

    /**
     * Checks if an asset should be dumped.
     *
     * @param string $name        The asset name
     * @param array  &$previously An array of previous visits
     *
     * @return Boolean Whether the asset should be dumped
     */
    private function checkAsset($name, array &$previously)
    {
        $formula = $this->am->hasFormula($name) ? serialize($this->am->getFormula($name)) : null;
        $asset = $this->am->get($name);

        $values = $this->getContainer()->getParameter('assetic.variables');
        $values = array_intersect_key($values, array_flip($asset->getVars()));

        if (empty($values)) {
            $mtime = $asset->getLastModified();
        } else {
            $writer = new AssetWriter(sys_get_temp_dir(), $this->getContainer()->getParameter('assetic.variables'));
            $ref = new \ReflectionMethod($writer, 'getCombinations');
            $ref->setAccessible(true);
            $combinations = $ref->invoke($writer, $asset->getVars());

            $mtime = 0;
            foreach ($combinations as $combination) {
                $asset->setValues($combination);
                $assetMtime = $asset->getLastModified();
                if ($assetMtime > $mtime) {
                    $mtime = $assetMtime;
                }
            }
        }

        if (isset($previously[$name])) {
            $changed = $previously[$name]['mtime'] != $mtime || $previously[$name]['formula'] != $formula;
        } else {
            $changed = true;
        }

        $previously[$name] = array('mtime' => $mtime, 'formula' => $formula);

        return $changed;
    }

    /**
     * Writes an asset.
     *
     * If the application or asset is in debug mode, each leaf asset will be
     * dumped as well.
     *
     * @param string          $name   An asset name
     * @param OutputInterface $output The command output
     */
    private function dumpAsset($name, OutputInterface $output)
    {
        if($this->getContainer()->getParameter('assetic.cache')==true){
            $asset = new \Assetic\Asset\AssetCache(
                $this->am->get($name),
                new \Assetic\Cache\FilesystemCache($this->getContainer()->getParameter('assetic.cache_dir'))
            );
        }else{
            $asset=$this->am->get($name);
        }

        $formula = $this->am->getFormula($name);

        // start by dumping the main asset
        $this->doDump($asset, $output);

        // dump each leaf if debug
        if (isset($formula[2]['debug']) ? $formula[2]['debug'] : $this->am->isDebug()) {
            if ($asset instanceof \Assetic\Asset\AssetCache){
                $refObj  = new \ReflectionObject( $asset );
                $refProp = $refObj->getProperty( 'asset' );
                $refProp->setAccessible( true );
                $assets = $refProp->getValue( $asset );
            }else{
                $assets=$asset;
            }
            foreach ($assets as $leaf) {
                $this->doDump($leaf, $output);
            }
        }
    }

    /**
     * Performs the asset dump.
     *
     * @param AssetInterface  $asset  An asset
     * @param OutputInterface $output The command output
     *
     * @throws RuntimeException If there is a problem writing the asset
     */
    private function doDump(AssetInterface $asset, OutputInterface $output)
    {
        $writer = new AssetWriter(sys_get_temp_dir(), $this->getContainer()->getParameter('assetic.variables'));
        $ref = new \ReflectionMethod($writer, 'getCombinations');
        $ref->setAccessible(true);
        $combinations = $ref->invoke($writer, $asset->getVars());

        foreach ($combinations as $combination) {
            $asset->setValues($combination);

            $target = rtrim($this->basePath, '/').'/'.str_replace('_controller/', '',
                PathUtils::resolvePath($asset->getTargetPath(), $asset->getVars(),
                    $asset->getValues()));
            if (!is_dir($dir = dirname($target))) {
                $output->writeln(sprintf(
                    '<comment>%s</comment> <info>[dir+]</info> %s',
                    date('H:i:s'),
                    $dir
                ));
                if (false === @mkdir($dir, 0777, true)) {
                    throw new \RuntimeException('Unable to create directory '.$dir);
                }
            }

            $output->writeln(sprintf(
                '<comment>%s</comment> <info>[file+]</info> %s',
                date('H:i:s'),
                $target
            ));
            if ($this->verbose) {
                //since AssetCache has no getAsset(Anyway,I will make a PR later), I can only use this way to get the $asset
                if($asset instanceof \Assetic\Asset\AssetCache){
                    $refObj  = new \ReflectionObject( $asset );
                    $refProp = $refObj->getProperty( 'asset' );
                    $refProp->setAccessible( true );
                    $temp_asset = $refProp->getValue( $asset );
                }else{
                    $temp_asset=$asset;
                }

                if ($temp_asset instanceof \Traversable) {
                    foreach ($temp_asset as $leaf) {
                        $root = $leaf->getSourceRoot();
                        $path = $leaf->getSourcePath();
                        $output->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
                    }
                } else {
                    $root = $asset->getSourceRoot();
                    $path = $asset->getSourcePath();
                    $output->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
                }
            }

            if (false === @file_put_contents($target, $asset->dump())) {
                throw new \RuntimeException('Unable to write file '.$target);
            }
        }
    }
}
