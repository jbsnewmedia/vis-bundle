<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\DependencyInjection;

use JBSNewMedia\VisBundle\DependencyInjection\VisExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class VisExtensionTest extends TestCase
{
    public function testLoadWithDefaultConfig(): void
    {
        $container = new ContainerBuilder();
        $extension = new VisExtension();

        $extension->load([], $container);

        $this->assertTrue($container->hasParameter('vis.locales'));
        $this->assertEquals(['en'], $container->getParameter('vis.locales'));
        $this->assertEquals('en', $container->getParameter('vis.default_locale'));
        $this->assertTrue($container->hasDefinition('JBSNewMedia\VisBundle\Service\Vis'));
    }

    public function testLoadWithCustomConfig(): void
    {
        $container = new ContainerBuilder();
        $extension = new VisExtension();

        $configs = [
            [
                'locales' => ['de', 'fr'],
                'default_locale' => 'de',
            ],
        ];

        $extension->load($configs, $container);

        $this->assertEquals(['de', 'fr'], $container->getParameter('vis.locales'));
        $this->assertEquals('de', $container->getParameter('vis.default_locale'));
    }

    public function testLoadWithEmptyLocalesUsesDefaultLocale(): void
    {
        $container = new ContainerBuilder();
        $extension = new VisExtension();

        $configs = [
            [
                'locales' => [],
                'default_locale' => 'it',
            ],
        ];

        $extension->load($configs, $container);

        $this->assertEquals(['it'], $container->getParameter('vis.locales'));
        $this->assertEquals('it', $container->getParameter('vis.default_locale'));
    }
}
