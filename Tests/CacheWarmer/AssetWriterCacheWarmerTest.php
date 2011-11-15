<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Tests\CacheWarmer;

use Symfony\Bundle\AsseticBundle\CacheWarmer\AssetWriterCacheWarmer;

use Symfony\Bundle\AsseticBundle\Tests\TestCase;

class AssetWriterCacheWarmerTest extends TestCase
{
    public function testWarmUp()
    {
        $am = $this->getMock('Assetic\\AssetManager');

        $writer = $this
            ->getMockBuilder('Assetic\\AssetWriter')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $writer
            ->expects($this->once())
            ->method('writeManagerAssets')
            ->with($am)
        ;

        $container = $this
            ->getMockBuilder('Symfony\\Component\\DependencyInjection\\Container')
            ->setConstructorArgs(array())
            ->getMock()
        ;

        $container
            ->expects($this->once())
            ->method('get')
            ->with('assetic.asset_manager')
            ->will($this->returnValue($am))
        ;

        $warmer = new AssetWriterCacheWarmer($container, $writer);
        $warmer->warmUp('/path/to/cache');
    }
}
