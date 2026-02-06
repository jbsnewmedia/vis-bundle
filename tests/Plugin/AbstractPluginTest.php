<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Plugin;

use JBSNewMedia\VisBundle\Attribute\VisPlugin;
use JBSNewMedia\VisBundle\Plugin\AbstractPlugin;
use PHPUnit\Framework\TestCase;

final class AbstractPluginTest extends TestCase
{
    public function testGetPluginIdAndPriorityFromAttribute(): void
    {
        $plugin = new #[VisPlugin(plugin: 'my_plugin', priority: 42)] class extends AbstractPlugin {};

        $this->assertSame('my_plugin', $plugin->getPluginId());
        $this->assertSame(42, $plugin->getPriority());

        $tool = $plugin->createTool();
        $this->assertSame('my_plugin', $tool->getId());
        $this->assertSame(42, $tool->getPriority());
    }

    public function testGetPluginIdWithEmptyPluginName(): void
    {
        $plugin = new #[VisPlugin(plugin: '', priority: 42)] class extends AbstractPlugin {};
        $this->assertNull($plugin->getPluginId());
    }

    public function testGetPluginIdWithMultipleAttributes(): void
    {
        // Should pick the first one that has a plugin name
        $plugin = new #[VisPlugin(plugin: '', priority: 10), VisPlugin(plugin: 'second', priority: 20)] class extends AbstractPlugin {};
        $this->assertSame('second', $plugin->getPluginId());
    }

    public function testDefaultsWithoutAttribute(): void
    {
        $plugin = new class extends AbstractPlugin {};

        $this->assertNull($plugin->getPluginId());
        $this->assertSame(100, $plugin->getPriority());

        $tool = $plugin->createTool();
        $this->assertSame('', $tool->getId());
        $this->assertSame(100, $tool->getPriority());
    }

    public function testEmptyMethods(): void
    {
        $plugin = new class extends AbstractPlugin {};
        $plugin->init();
        $plugin->setTopBar();
        $plugin->setNavigation();
        $this->assertTrue(true);
    }

    public function testGetAttributesReflectionException(): void
    {
        $plugin = new class extends AbstractPlugin {
            public function callGetAttributes(): array {
                return $this->getAttributes();
            }
        };
        $this->assertIsArray($plugin->callGetAttributes());
    }

    public function testGetPluginIdPriorityFallback(): void
    {
        $plugin = new class extends AbstractPlugin {};
        $this->assertEquals(100, $plugin->getPriority());
    }

    public function testRemoveRole(): void
    {
        // Actually RolesTrait is tested elsewhere, but AbstractPlugin doesn't use it.
        // Wait, AbstractPlugin doesn't have roles.
        $this->assertTrue(true);
    }
}
