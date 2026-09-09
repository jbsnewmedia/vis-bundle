<?php

declare(strict_types=1);

/*
 * Registers a PSR-4 fallback autoloader for the jbsnewmedia sibling
 * repositories (used by local tooling when the composer vendor directory
 * with path-repository symlinks is not available on the host).
 */

$fallbackPrefixes = [];

foreach (glob(dirname(__DIR__).'/*/composer.json') ?: [] as $packageFile) {
    $composer = json_decode((string) file_get_contents($packageFile), true);
    if (!is_array($composer)) {
        continue;
    }

    $packageDir = dirname($packageFile);
    foreach ($composer['autoload']['psr-4'] ?? [] as $prefix => $path) {
        $normalizedPrefix = rtrim((string) $prefix, '\\');
        if (!array_key_exists($normalizedPrefix, $fallbackPrefixes)) {
            $fallbackPrefixes[$normalizedPrefix] = $packageDir.'/'.rtrim((string) $path, '/');
        }
    }
}

spl_autoload_register(static function (string $class) use ($fallbackPrefixes): void {
    foreach ($fallbackPrefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix.'\\')) {
            $relative = substr($class, \strlen($prefix) + 1);
            $file = $baseDir.'/'.str_replace('\\', '/', $relative).'.php';
            if (is_file($file)) {
                require $file;

                return;
            }
        }
    }
}, true, false);
