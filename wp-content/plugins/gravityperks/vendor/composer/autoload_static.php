<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9b6c5e93c8723be56466334ea2a3a43d
{
    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Parsedown' => 
            array (
                0 => __DIR__ . '/..' . '/erusev/parsedown',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit9b6c5e93c8723be56466334ea2a3a43d::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit9b6c5e93c8723be56466334ea2a3a43d::$classMap;

        }, null, ClassLoader::class);
    }
}