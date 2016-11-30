<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services tagged as workers to the asset factory.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class AssetFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('assetic.asset_factory')) {
            return;
        }

        $factory = $container->getDefinition('assetic.asset_factory');
        $services = $container->findTaggedServiceIds('assetic.factory_worker');

        // Ascending sort by priority, default is 0
        uasort($services, function($a, $b) {
            $p1 = isset($a[0]['priority']) ? $a[0]['priority'] : 0;
            $p2 = isset($b[0]['priority']) ? $b[0]['priority'] : 0;

            return $p1 - $p2;
        });

        foreach ($services as $id => $attr) {
            $factory->addMethodCall('addWorker', array(new Reference($id)));
        }
    }
}
