<?php

require_once $_SERVER['SYMFONY'].'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespace('Symfony', $_SERVER['SYMFONY']);
$loader->registerNamespace('Assetic', $_SERVER['ASSETIC']);
$loader->registerPrefix('Twig_', $_SERVER['TWIG']);
$loader->register();

spl_autoload_register(function($class)
{
    if (0 === strpos($class, 'Symfony\\Bundle\\AsseticBundle\\') &&
        file_exists($file = __DIR__.'/../'.implode('/', array_slice(explode('\\', $class), 3)).'.php')) {
        require_once $file;
    }
});
