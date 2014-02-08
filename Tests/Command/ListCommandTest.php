<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Tests\Command;

use Symfony\Bundle\AsseticBundle\Command\ListCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends \PHPUnit_Framework_TestCase
{
    private $application;
    private $definition;
    private $kernel;
    private $container;
    private $am;

    protected function setUp()
    {
        if (!class_exists('Symfony\Component\Console\Application')) {
            $this->markTestSkipped('Symfony Console is not available.');
        }

        $this->application = $this->getMockBuilder('Symfony\\Bundle\\FrameworkBundle\\Console\\Application')
            ->disableOriginalConstructor()
            ->getMock();
        $this->definition = $this->getMockBuilder('Symfony\\Component\\Console\\Input\\InputDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $this->kernel = $this->getMock('Symfony\\Component\\HttpKernel\\KernelInterface');
        $this->helperSet = $this->getMock('Symfony\\Component\\Console\\Helper\\HelperSet');
        $this->container = $this->getMock('Symfony\\Component\\DependencyInjection\\ContainerInterface');
        $this->am = $this->getMockBuilder('Assetic\\Factory\\LazyAssetManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->application->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($this->definition));
        $this->definition->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(array()));
        $this->definition->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue(array(
                new InputOption('--verbose', '-v', InputOption::VALUE_NONE, 'Increase verbosity of messages.'),
                new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'),
                new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'),
            )));
        $this->application->expects($this->any())
            ->method('getKernel')
            ->will($this->returnValue($this->kernel));
        $this->application->expects($this->once())
            ->method('getHelperSet')
            ->will($this->returnValue($this->helperSet));
        $this->kernel->expects($this->any())
            ->method('getContainer')
            ->will($this->returnValue($this->container));

        $this->container->expects($this->once())
            ->method('get')
            ->with('assetic.asset_manager')
            ->will($this->returnValue($this->am));

        $this->command = new ListCommand();
        $this->command->setApplication($this->application);
    }

    public function testEmptyAssetManager()
    {
        $this->am->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue(array()));

        $this->runCommandGetOutput();
    }
    
    private function runCommandGetOutput($inputArray = array()) {
        
        $commandTester = new CommandTester($this->command);
        $commandTester->execute($inputArray);
        
        return $commandTester->getDisplay();
    }

    public function testListOne()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $this->am->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue(array('test_asset')));
        $this->am->expects($this->once())
            ->method('get')
            ->with('test_asset')
            ->will($this->returnValue($asset));
        $asset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.css'));

        $output = $this->runCommandGetOutput();

        $this->assertEquals("test_asset.css" . PHP_EOL, $output);
    }

    public function testListMultipleAssets()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetCollection');
        $leaf1 = $this->getMock('Assetic\\Asset\\AssetInterface');
        $leaf2 = $this->getMock('Assetic\\Asset\\AssetInterface');

        $this->am->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue(array('test_asset')));
        $this->am->expects($this->once())
            ->method('get')
            ->with('test_asset')
            ->will($this->returnValue($asset));
        $asset->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator(array($leaf1, $leaf2))));
        $asset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue(null));
        $leaf1->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.css'));
        $leaf2->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.js'));

        $output = $this->runCommandGetOutput();

        $this->assertEquals("test_asset.css" . PHP_EOL . "test_asset.js" . PHP_EOL, $output);
    }
    
    public function testListMultipleAssetsWithPrint0()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetCollection');
        $leaf1 = $this->getMock('Assetic\\Asset\\AssetInterface');
        $leaf2 = $this->getMock('Assetic\\Asset\\AssetInterface');

        $this->am->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue(array('test_asset')));
        $this->am->expects($this->once())
            ->method('get')
            ->with('test_asset')
            ->will($this->returnValue($asset));
        $asset->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator(array($leaf1, $leaf2))));
        $asset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue(null));
        $leaf1->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.css'));
        $leaf2->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.js'));

        $output = $this->runCommandGetOutput(array('--print0' => true));

        $this->assertEquals("test_asset.css" . chr(0) . "test_asset.js" . chr(0), $output);
    }
    
    public function testListMultiLevelAssets()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetCollection');
        $leaf1 = $this->getMock('Assetic\\Asset\\AssetInterface');
        $leaf2 = $this->getMock('Assetic\\Asset\\AssetInterface');
        $firstLevelAsset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $this->am->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue(array(
                'test_asset','first_level_asset'
            )));
            
        $this->am->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(array(
                array('test_asset', $asset),
                array('first_level_asset', $firstLevelAsset)
            )));
            
        $asset->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator(array($leaf1, $leaf2))));
        $asset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue(null));
            
        $leaf1->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.css'));
        $leaf2->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.js'));
            
        $firstLevelAsset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('first_level_asset.js'));

        $output = $this->runCommandGetOutput();

        $this->assertEquals("test_asset.css" . PHP_EOL . "test_asset.js" . PHP_EOL . "first_level_asset.js" . PHP_EOL, $output);
    }
    
    public function testListFullPathsAssets()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetCollection');
        $leaf1 = $this->getMock('Assetic\\Asset\\AssetInterface');
        $leaf2 = $this->getMock('Assetic\\Asset\\AssetInterface');
        $firstLevelAsset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $this->am->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue(array(
                'test_asset','first_level_asset'
            )));
            
        $this->am->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(array(
                array('test_asset', $asset),
                array('first_level_asset', $firstLevelAsset)
            )));
            
        $asset->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator(array($leaf1, $leaf2))));
        $asset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue(null));
            
        $leaf1->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.css'));
        $leaf1->expects($this->once())
            ->method('getSourceRoot')
            ->will($this->returnValue('/path_to'));
            
        $leaf2->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.js'));
        $leaf2->expects($this->once())
            ->method('getSourceRoot')
            ->will($this->returnValue('/another_path'));
            
        $firstLevelAsset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('first_level_asset.js'));
        $firstLevelAsset->expects($this->once())
            ->method('getSourceRoot')
            ->will($this->returnValue('/path_to'));

        $output = $this->runCommandGetOutput(array('--paths' => true));

        $this->assertEquals(
            "/path_to/test_asset.css" . PHP_EOL .
            "/another_path/test_asset.js" . PHP_EOL .
            "/path_to/first_level_asset.js" . PHP_EOL,
        $output);
    }

    public function testMD5Output()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetCollection');
        $leaf1 = $this->getMock('Assetic\\Asset\\AssetInterface');
        $leaf2 = $this->getMock('Assetic\\Asset\\AssetInterface');
        $firstLevelAsset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $this->am->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue(array(
                'test_asset','first_level_asset'
            )));
            
        $this->am->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(array(
                array('test_asset', $asset),
                array('first_level_asset', $firstLevelAsset)
            )));
            
        $asset->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator(array($leaf1, $leaf2))));
        $asset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue(null));
        $asset->expects($this->never())
            ->method('dump');
            
        $leaf1->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.css'));
        $leaf1->expects($this->once())
            ->method('getSourceRoot')
            ->will($this->returnValue('/path_to'));
        $leaf1->expects($this->once())
            ->method('dump')
            ->will($this->returnValue("test_content\n")); //87978e0dfadc2f75cafc0d21600eaa55

        $leaf2->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('test_asset.js'));
        $leaf2->expects($this->once())
            ->method('getSourceRoot')
            ->will($this->returnValue('/another_path'));
        $leaf2->expects($this->once())
            ->method('dump')
            ->will($this->returnValue("other content\n")); //33fe21c6bdf6786411e4f18272956536
            
        $firstLevelAsset->expects($this->once())
            ->method('getSourcePath')
            ->will($this->returnValue('first_level_asset.js'));
        $firstLevelAsset->expects($this->once())
            ->method('getSourceRoot')
            ->will($this->returnValue('/path_to'));
        $firstLevelAsset->expects($this->once())
            ->method('dump')
            ->will($this->returnValue("third\n")); //aa62cba149c51923916eff46f80fe74c

        $output = $this->runCommandGetOutput(array('--paths' => true, '--md5' => 'dump'));

        $this->assertEquals(
            "87978e0dfadc2f75cafc0d21600eaa55 /path_to/test_asset.css" . PHP_EOL .
            "33fe21c6bdf6786411e4f18272956536 /another_path/test_asset.js" . PHP_EOL .
            "aa62cba149c51923916eff46f80fe74c /path_to/first_level_asset.js" . PHP_EOL,
        $output);
    }
}
