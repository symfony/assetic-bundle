<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Tests\Factory\Resource;

use Symfony\Bundle\AsseticBundle\Factory\Resource\FileResource;

use Symfony\Bundle\AsseticBundle\Tests\TestCase;

class FileResourceTest extends TestCase
{
    private $loader;

    protected function setUp()
    {
        parent::setUp();

        $this->loader = $this->getMock('Symfony\\Component\\Templating\\Loader\\LoaderInterface');
    }

    protected function tearDown()
    {
        $this->loader = null;
    }

    public function testCastAsString()
    {
        $baseDir = '/path/to/MyBundle/Resources/views/';
        $resource = new FileResource($this->loader, 'MyBundle', $baseDir, $baseDir.'Section/template.html.twig');
        $this->assertEquals('MyBundle:Section:template.html.twig', (string) $resource);
    }
}
