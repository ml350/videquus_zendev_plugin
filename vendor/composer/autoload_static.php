<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6790a62c79e4d7719e2f0b3afdda9301
{
    public static $prefixLengthsPsr4 = array (
        'Z' => 
        array (
            'ZENDEVPLUGIN\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ZENDEVPLUGIN\\' => 
        array (
            0 => __DIR__ . '/../..' . '/inc',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6790a62c79e4d7719e2f0b3afdda9301::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6790a62c79e4d7719e2f0b3afdda9301::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
