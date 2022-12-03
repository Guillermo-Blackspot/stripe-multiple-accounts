<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2b3c63e91ffb3bb1c82a34efb5f86e14
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
        'B' => 
        array (
            'BlackSpot\\StripeMultipleAccounts\\' => 33,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
        'BlackSpot\\StripeMultipleAccounts\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2b3c63e91ffb3bb1c82a34efb5f86e14::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2b3c63e91ffb3bb1c82a34efb5f86e14::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit2b3c63e91ffb3bb1c82a34efb5f86e14::$classMap;

        }, null, ClassLoader::class);
    }
}
