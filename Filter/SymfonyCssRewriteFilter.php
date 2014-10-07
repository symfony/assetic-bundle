<?php

namespace Symfony\Bundle\AsseticBundle\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\CssRewriteFilter;

/**
 * Fixes relative CSS urls.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class SymfonyCssRewriteFilter extends CssRewriteFilter
{
    protected function pathRewriter(AssetInterface $asset, $host, $path)
    {
        if ($pos = strpos($path, 'Resources/public'))
        {
            $root = $asset->getSourceRoot();
            $bundleName = basename(dirname($root)).basename($root);
            $bundleName = strtolower(str_replace('Bundle', '', $bundleName));
            $path = '/bundles/'.$bundleName.mb_substr($path, $pos+16);
        }
        return array($host, $path);
    }
}
