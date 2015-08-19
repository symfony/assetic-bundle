<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Templating;

use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;

/**
 * The static "assetic" templating helper.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class StaticAsseticHelper extends AsseticHelper
{
    private $assetsHelper;

    /**
     * Constructor.
     *
     * @param CoreAssetsHelper|AssetsHelper $assetsHelper The assets helper
     * @param AssetFactory     $factory      The asset factory
     */
    public function __construct($assetsHelper, AssetFactory $factory)
    {
        // Symfony <2.7 BC
        if (!$assetsHelper instanceof AssetsHelper && !$assetsHelper instanceof CoreAssetsHelper) {
            throw new \InvalidArgumentException('Argument 1 passed to '.__METHOD__.' must be an instance of Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper or Symfony\Component\Templating\Helper\CoreAssetsHelper, instance of '.get_class($assetsHelper).' given');
        }

        $this->assetsHelper = $assetsHelper;

        parent::__construct($factory);
    }

    protected function getAssetUrl(AssetInterface $asset, $options = array())
    {
        return $this->assetsHelper->getUrl($asset->getTargetPath(), isset($options['package']) ? $options['package'] : null);
    }
}
