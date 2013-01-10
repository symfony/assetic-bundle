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
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group functional
 */
class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    static protected $cacheDir;

    static public function setUpBeforeClass()
    {
        if (!class_exists('Assetic\\AssetManager')) {
            self::markTestSkipped('Assetic is not available.');
        }

        self::$cacheDir = __DIR__.'/Resources/cache';
        if (file_exists(self::$cacheDir)) {
            $filesystem = new Filesystem();
            $filesystem->remove(self::$cacheDir);
        }

        mkdir(self::$cacheDir, 0777, true);
    }

    static public function tearDownAfterClass()
    {
        $filesystem = new Filesystem();
        $filesystem->remove(self::$cacheDir);
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
