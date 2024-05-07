<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3c08c6e6944aec7062583eddb7efbd8e
{
    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'proj4php\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'proj4php\\' => 
        array (
            0 => __DIR__ . '/..' . '/proj4php/proj4php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3c08c6e6944aec7062583eddb7efbd8e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3c08c6e6944aec7062583eddb7efbd8e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3c08c6e6944aec7062583eddb7efbd8e::$classMap;

        }, null, ClassLoader::class);
    }
}