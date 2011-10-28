<?php

/*
 * This file is part of the Symfony framework.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Filter\Sass;

use Assetic\Filter\Sass\SassFilter as AsseticSassFilter;

/**
 * Extending Sass-Filter to add addLoadPaths functionality.
 *
 * @author Mike Lohmann <mike.h.lohmann@googlemail.com>
 */
class SassFilter extends AsseticSassFilter
{
    /**
     * 
     * Iterates through a list of loadPaths for Sass (from config)
     * @see \Resources\config\filters\sass.xml
     * 
     * @param array $loadPaths
     */
    public function addLoadPaths(array $loadPaths = array())
    {
        foreach ($loadPaths as $loadPath) {
            $this->addLoadPath($loadPath);
        }
    }
}
