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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * This pass adds Assetic routes when use_controller is true.
 *
 * @author Bart van den Burg <bart@samson-it.nl>
 */
class ScssPhpFilterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('assetic.filter.scssphp') || !$container->hasParameter('assetic.filter.scssphp.compass') || !$container->getParameter('assetic.filter.scssphp.compass')) {
            return;
        }

        $filter = $container->getDefinition('assetic.filter.scssphp');
        $filter->addMethodCall('enableCompass');
    }
}
