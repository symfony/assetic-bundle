<?php

namespace Symfony\Bundle\AsseticBundle\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\HashableInterface;

class HashableFilter implements FilterInterface, HashableInterface
{

    /**
     * @var string
     */
    private $hash;

    /**
     * @param string $hash
     */
    public function __construct($hash)
    {
        $this->hash = $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function filterDump(AssetInterface $asset)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hash()
    {
        return (string) $this->hash;
    }
}
