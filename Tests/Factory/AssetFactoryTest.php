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
    private $kernel;
    private $factory;
    private $parameterBag;
    private $container;

    protected function setUp()
    {
        if (!class_exists('Assetic\\AssetManager')) {
            $this->markTestSkipped('Assetic is not available.');
        }

        $this->kernel = $this->getMockBuilder('Symfony\\Component\\HttpKernel\\KernelInterface')->getMock();
        $this->container = $this->getMockBuilder('Symfony\\Component\\DependencyInjection\\ContainerInterface')->getMock();
        $this->parameterBag = $this->getMockBuilder('Symfony\\Component\\DependencyInjection\\ParameterBag\\ParameterBagInterface')->getMock();
        $this->factory = new AssetFactory($this->kernel, $this->container, $this->parameterBag, '/path/to/web');
    }

    public function testBundleNotation()
    {
        $input = '@MyBundle/Resources/css/main.css';
        $bundle = $this->getMockBuilder('Symfony\\Component\\HttpKernel\\Bundle\\BundleInterface')->getMock();

        $this->parameterBag->expects($this->once())
            ->method('resolveValue')
            ->will($this->returnCallback(function ($v) { return $v; }));
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

    /**
     * @dataProvider getGlobs
     */
    public function testBundleGlobNotation($input)
    {
        $bundle = $this->getMockBuilder('Symfony\\Component\\HttpKernel\\Bundle\\BundleInterface')->getMock();

        $this->parameterBag->expects($this->once())
            ->method('resolveValue')
            ->will($this->returnCallback(function ($v) { return $v; }));
        $this->kernel->expects($this->once())
            ->method('getBundle')
            ->with('MyBundle')
            ->will($this->returnValue($bundle));
        $this->kernel->expects($this->once())
            ->method('locateResource')
            ->with('@MyBundle/Resources/css/', $this->anything(), false)
            ->will($this->returnValue(array('/path/to/MyBundle/Resources/css/')));
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

    public function getGlobs()
    {
        return array(
            array('@MyBundle/Resources/css/*'),
            array('@MyBundle/Resources/css/*/*.css'),
        );
    }
}
