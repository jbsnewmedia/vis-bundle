<?php

declare(strict_types=1);

/*
 * Bootstrap for PHPUnit runs from vendor-bin/phpunit.
 *
 * Uses the vendor-bin autoloader (self-contained Symfony/twig stack) plus
 * the jbsnewmedia sibling repositories. Deliberately does NOT include the
 * application vendor autoloader to avoid mixing PHPUnit major versions.
 */

require __DIR__.'/vendor-bin/phpunit/vendor/autoload.php';

require __DIR__.'/autoload-siblings.php';
