<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Tests;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Util\Filesystem;

use Symfony\Bundle\AsseticBundle\Tests\TestCase;

/**
 * @group functional
 */
class FunctionalTest extends TestCase
{
    protected $cacheDir;

    protected function setUp()
    {
        parent::setUp();

        $this->cacheDir = __DIR__.'/Resources/cache';
        if (file_exists($this->cacheDir)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->cacheDir);
        }

        mkdir($this->cacheDir, 0777, true);
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->cacheDir);

        $this->cacheDir = null;
    }

    public function testTwigRenderDebug()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();
        $container->enterScope('request');
        $container->set('request', new Request());

        $content = $container->get('templating')->render('::layout.html.twig');
        $crawler = new Crawler($content);

        $this->assertEquals(3, count($crawler->filter('link[href$=".css"]')));
        $this->assertEquals(2, count($crawler->filter('script[src$=".js"]')));
    }

    public function testPhpRenderDebug()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();
        $container->enterScope('request');
        $container->set('request', new Request());

        $content = $container->get('templating')->render('::layout.html.php');
        $crawler = new Crawler($content);

        $this->assertEquals(3, count($crawler->filter('link[href$=".css"]')));
        $this->assertEquals(2, count($crawler->filter('script[src$=".js"]')));
    }
}
