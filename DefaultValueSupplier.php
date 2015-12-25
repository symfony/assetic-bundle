<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle;

use Assetic\ValueSupplierInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default Value Supplier.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DefaultValueSupplier implements ValueSupplierInterface
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getValues()
    {
        if ($this->container->has('request_stack')) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
            if (null === $request) {
                return array();
            }
        } else {
            if (!$this->container->isScopeActive('request')) {
                return array();
            }
            $request = $this->container->get('request');
        }

        return array(
            'locale' => $request->getLocale(),
            'env'    => $this->container->getParameter('kernel.environment'),
        );
    }
}
