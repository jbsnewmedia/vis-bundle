<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\DependencyInjection;

use JBSNewMedia\VisBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertEquals([], $config['locales']);
        $this->assertEquals('en', $config['default_locale']);
    }

    public function testCustomConfig(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [
            'vis' => [
                'locales' => ['de', 'fr'],
                'default_locale' => 'de'
            ]
        ]);

        $this->assertEquals(['de', 'fr'], $config['locales']);
        $this->assertEquals('de', $config['default_locale']);
    }
}
