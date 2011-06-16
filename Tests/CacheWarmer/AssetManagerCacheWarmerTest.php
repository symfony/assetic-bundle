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

use Symfony\Bundle\AsseticBundle\CacheWarmer\AssetManagerCacheWarmer;

use Symfony\Bundle\AsseticBundle\Tests\TestCase;

class AssetManagerCacheWarmerTest extends TestCase
{
    public function testWarmUp()
    {
        $am = $this
            ->getMockBuilder('Assetic\\Factory\\LazyAssetManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $am->expects($this->once())->method('load');

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

        $warmer = new AssetManagerCacheWarmer($container);
        $warmer->warmUp('/path/to/cache');
    }
}
