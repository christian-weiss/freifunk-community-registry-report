<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitedba3253db2c9c807bb06e9213e0c23a
{
    public static $prefixLengthsPsr4 = array (
        'J' => 
        array (
            'JsonSchema\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'JsonSchema\\' => 
        array (
            0 => __DIR__ . '/..' . '/justinrainbow/json-schema/src/JsonSchema',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Rs\\Json' => 
            array (
                0 => __DIR__ . '/..' . '/php-jsonpointer/php-jsonpointer/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitedba3253db2c9c807bb06e9213e0c23a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitedba3253db2c9c807bb06e9213e0c23a::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitedba3253db2c9c807bb06e9213e0c23a::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}