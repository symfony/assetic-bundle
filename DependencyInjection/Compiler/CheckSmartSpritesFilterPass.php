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

/**
 * Checks that the location of the SmartSprites JAR has been configured.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class CheckSmartSpritesFilterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('assetic.filter.smartsprites') &&
            !$container->getParameterBag()->resolveValue($container->getParameter('assetic.filter.smartsprites.path'))) {
            throw new \RuntimeException('The "assetic.filters.smartsprites" configuration requires a "path" value.');
        }
    }
}
