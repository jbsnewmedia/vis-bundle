<?php

declare(strict_types=1);

/*
 * Bootstrap for local tooling (phpstan).
 *
 * Prefers the bundle's own composer vendor directory, falls back to the
 * sibling vis application vendor directory and finally registers the
 * jbsnewmedia sibling repositories as PSR-4 fallback.
 *
 * The class_exists() calls force-load external dependencies so PHPStan
 * indexes them even when they are only resolvable through the fallback
 * autoloader.
 */

$autoloaders = [
    __DIR__.'/vendor/autoload.php',
    dirname(__DIR__).'/vis/vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require_once $autoloader;

        break;
    }
}

require __DIR__.'/autoload-siblings.php';

class_exists(\JBSNewMedia\BootstrapBundle\Service\ScssCompilerFactory::class);
class_exists(\JBSNewMedia\AssetComposerBundle\Service\AssetComposer::class);
