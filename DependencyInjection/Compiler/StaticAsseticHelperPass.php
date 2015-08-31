<?php

namespace Symfony\Bundle\AsseticBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class StaticAsseticHelperPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('assetic.helper.static')) {
            return;
        }

        if (!$container->hasDefinition('templating.helper.assets')) {
            // The AssetsHelper is gone in Symfony 3.0
            $container->removeDefinition('assetic.helper.static');

            return;
        }

        $definition = $container->getDefinition('assetic.helper.static');

        if (!method_exists($definition, 'setShared')
            && 'request' === $container->getDefinition('templating.helper.assets')->getScope()) {
            $definition->setScope('request');
        }
    }
}
