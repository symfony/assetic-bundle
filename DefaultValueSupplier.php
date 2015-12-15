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
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default Value Supplier.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DefaultValueSupplier implements ValueSupplierInterface
{
    protected $container;

    private $requestStack;

    public function __construct(ContainerInterface $container, RequestStack $requestStack = null)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;
    }

    public function getValues()
    {
        $request = $this->getCurrentRequest();

        if (!$request) {
            return array();
        }

        return array(
            'locale' => $request->getLocale(),
            'env'    => $this->container->getParameter('kernel.environment'),
        );
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    private function getCurrentRequest()
    {
        $request = null;

        if ($this->requestStack) {
            $request = $this->requestStack->getCurrentRequest();
        } elseif ($this->container->isScopeActive('request')) {
            $request = $this->container->get('request');
        }

        return $request;
    }
}
