<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Tests\Factory;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;

class AssetFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $kernel;
    protected $factory;
    protected $container;

    protected function setUp()
    {
        if (!class_exists('Assetic\\AssetManager')) {
            $this->markTestSkipped('Assetic is not available.');
        }

        $this->kernel = $this->getMock('Symfony\\Component\\HttpKernel\\KernelInterface');
        $this->container = $this->getMock('Symfony\\Component\\DependencyInjection\\ContainerInterface');
        $this->parameterBag = $this->getMock('Symfony\\Component\\DependencyInjection\\ParameterBag\\ParameterBagInterface');
        $this->factory = new AssetFactory($this->kernel, $this->container, $this->parameterBag, '/path/to/web');
    }

    public function testBundleNotation()
    {
        $input = '@MyBundle/Resources/css/main.css';
        $bundle = $this->getMock('Symfony\\Component\\HttpKernel\\Bundle\\BundleInterface');

        $this->parameterBag->expects($this->once())
            ->method('resolveValue')
            ->will($this->returnCallback(function($v) { return $v; }));
        $this->kernel->expects($this->once())
            ->method('getBundle')
            ->with('MyBundle')
            ->will($this->returnValue($bundle));
        $this->kernel->expects($this->once())
            ->method('locateResource')
            ->with($input)
            ->will($this->returnValue('/path/to/MyBundle/Resources/css/main.css'));
        $bundle->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('/path/to/MyBundle'));

        $coll = $this->factory->createAsset($input)->all();
        $asset = $coll[0];

        $this->assertEquals('/path/to/MyBundle', $asset->getSourceRoot(), '->createAsset() sets the asset root');
        $this->assertEquals('Resources/css/main.css', $asset->getSourcePath(), '->createAsset() sets the asset path');
       
    }
       
    public function testBundleNotationFindInAppResource()
    {
        $input = '@MyBundle/Resources/css/main.css';
        $bundle = $this->getMock('Symfony\\Component\\HttpKernel\\Bundle\\BundleInterface');
        $tmprootdir = sys_get_temp_dir() . '/rootdir';
        $resources_root =  $tmprootdir . '/dir/app/Resources/MyBundle';
        $resource = $resources_root . '/css/main.css';
        
        $this->parameterBag->expects($this->once())
            ->method('resolveValue')
            ->will($this->returnCallback(function($v) { return $v; }));
        //Search in Resources for existing css
        $this->kernel->expects($this->once())
            ->method('getRootDir')
            ->will($this->returnValue($tmprootdir . '/dir/app'));
        $this->kernel->expects($this->once())
            ->method('locateResource')
            ->with($input)
            ->will($this->returnValue($resource));
        $bundle->expects($this->never())
            ->method('getPath')
            ->will($this->returnValue('/path/to/MyBundle'));
        
        $pathinfos = pathinfo($resource);
        
        if(is_dir($tmprootdir)) {
            $this->rrmdir($tmprootdir);
        }
        
        mkdir($pathinfos['dirname'], 0777,true);
        touch($resource);

        $coll = $this->factory->createAsset($input)->all();
        $asset = $coll[0];

        $this->assertEquals($resources_root, $asset->getSourceRoot(), '->createAsset() sets the asset root');
        $this->assertEquals('css/main.css', $asset->getSourcePath(), '->createAsset() sets the asset path');
        
        $this->rrmdir($tmprootdir);
     
    }
    
    /**
     * @see http://php.net/manual/de/function.rmdir.php
     */
    private function rrmdir($dir) 
    {
       if (is_dir($dir)) {
         $objects = scandir($dir);
         foreach ($objects as $object) {
           if ($object != "." && $object != "..") {
             if (filetype($dir."/".$object) == "dir") {
                 $this->rrmdir($dir."/".$object); 
             } else {
                 unlink($dir."/".$object);   
             }
           }
         }
         reset($objects);
         rmdir($dir);
   }
 }
    
    /**
     * @dataProvider getGlobs
     */
    public function testBundleGlobNotation($input)
    {
        $bundle = $this->getMock('Symfony\\Component\\HttpKernel\\Bundle\\BundleInterface');

        $this->parameterBag->expects($this->once())
            ->method('resolveValue')
            ->will($this->returnCallback(function($v) { return $v; }));
        $this->kernel->expects($this->once())
            ->method('getBundle')
            ->with('MyBundle')
            ->will($this->returnValue($bundle));
        $this->kernel->expects($this->once())
            ->method('locateResource')
            ->with('@MyBundle/Resources/css/')
            ->will($this->returnValue('/path/to/MyBundle/Resources/css/'));
        $bundle->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('/path/to/MyBundle'));

        $coll = $this->factory->createAsset($input)->all();
        $asset = $coll[0];

        $this->assertEquals('/path/to/MyBundle', $asset->getSourceRoot(), '->createAsset() sets the asset root');
        $this->assertNull($asset->getSourcePath(), '->createAsset() sets the asset path to null');
    }
    
    /**
     * @dataProvider getGlobs
     */
    public function testBundleGlobNotationFindInAppResource($input)
    {
        $bundle = $this->getMock('Symfony\\Component\\HttpKernel\\Bundle\\BundleInterface');
        $tmprootdir = sys_get_temp_dir() . '/rootdir';
        $resources_root =  $tmprootdir . '/dir/app/Resources/MyBundle';
        $resource_1 = $resources_root . '/css/main_1.css';
        $resource_2 = $resources_root . '/css/main_2.css';
        $resource_3 = $resources_root . '/css/main/main_3.css';
        
        $this->parameterBag->expects($this->once())
            ->method('resolveValue')
            ->will($this->returnCallback(function($v) { return $v; }));
        $this->kernel->expects($this->once())
            ->method('getBundle')
            ->with('MyBundle')
            ->will($this->returnValue($bundle));
        $this->kernel->expects($this->once())
            ->method('locateResource')
            ->with('@MyBundle/Resources/css/')
            ->will($this->returnValue($resources_root . '/css/'));
        $bundle->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue($resources_root));
        
        $pathinfos = pathinfo($resource_1);
        
        if(is_dir($tmprootdir)) {
            $this->rrmdir($tmprootdir);
        }
        
        mkdir($pathinfos['dirname'], 0777,true);
        touch($resource_1);
        touch($resource_2);
        
        $pathinfos = pathinfo($resource_3);
        mkdir($pathinfos['dirname'], 0777,true);
        touch($resource_3);
        
        $coll = $this->factory->createAsset($input)->all();
        $asset = $coll[0];

        $this->assertEquals($resources_root, $asset->getSourceRoot(), '->createAsset() sets the asset root');
        $this->assertNull($asset->getSourcePath(), '->createAsset() sets the asset path to null');
        
        $this->rrmdir($tmprootdir);
    }

    public function getGlobs()
    {
        return array(
            array('@MyBundle/Resources/css/*'),
            array('@MyBundle/Resources/css/*/*.css'),
        );
    }
}
