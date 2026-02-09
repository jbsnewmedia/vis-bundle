<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Service;

use JBSNewMedia\VisBundle\Plugin\PluginInterface;
use JBSNewMedia\VisBundle\Service\VisPluginCollector;
use PHPUnit\Framework\TestCase;

class VisPluginCollectorTest extends TestCase
{
    public function testProcessAll(): void
    {
        $plugin1 = $this->createMock(PluginInterface::class);
        $plugin1->expects($this->once())->method('init');
        $plugin1->expects($this->once())->method('setNavigation');
        $plugin1->expects($this->once())->method('setTopBar');

        $plugin2 = $this->createMock(PluginInterface::class);
        $plugin2->expects($this->once())->method('init');
        $plugin2->expects($this->once())->method('setNavigation');
        $plugin2->expects($this->once())->method('setTopBar');

        $collector = new VisPluginCollector([$plugin1, $plugin2]);
        $collector->processAll();
    }

    public function testProcessAllOrder(): void
    {
        $log = [];
        $plugin1 = $this->createMock(PluginInterface::class);
        $plugin1->method('init')->willReturnCallback(function() use (&$log) { $log[] = 'p1_init'; });
        $plugin1->method('setTopBar')->willReturnCallback(function() use (&$log) { $log[] = 'p1_top'; });
        $plugin1->method('setNavigation')->willReturnCallback(function() use (&$log) { $log[] = 'p1_nav'; });

        $plugin2 = $this->createMock(PluginInterface::class);
        $plugin2->method('init')->willReturnCallback(function() use (&$log) { $log[] = 'p2_init'; });
        $plugin2->method('setTopBar')->willReturnCallback(function() use (&$log) { $log[] = 'p2_top'; });
        $plugin2->method('setNavigation')->willReturnCallback(function() use (&$log) { $log[] = 'p2_nav'; });

        // TaggedIterator is expected DESC, processAll reverses it to ASC (p1 then p2)
        $collector = new VisPluginCollector([$plugin2, $plugin1]);
        $collector->processAll();

        $expected = [
            'p1_init', 'p2_init',
            'p1_top', 'p2_top',
            'p1_nav', 'p2_nav'
        ];
        $this->assertEquals($expected, $log);
    }

    public function testGetServices(): void
    {
        $plugin1 = $this->createMock(PluginInterface::class);
        $plugin2 = $this->createMock(PluginInterface::class);

        // TaggedIterator is expected to be ordered DESC by priority,
        // VisPluginCollector reverses it to ASC.
        $collector = new VisPluginCollector([$plugin2, $plugin1]);
        $services = $collector->getServices();

        $this->assertCount(2, $services);
        $this->assertSame($plugin1, $services[0]);
        $this->assertSame($plugin2, $services[1]);
    }

    public function testGetByPlugin(): void
    {
        $plugin1 = $this->createMock(PluginInterface::class);
        $plugin1->method('getPluginId')->willReturn('plugin1');

        $plugin2 = $this->createMock(PluginInterface::class);
        $plugin2->method('getPluginId')->willReturn('plugin2');

        $collector = new VisPluginCollector([$plugin2, $plugin1]);

        $this->assertSame($plugin1, $collector->getByPlugin('plugin1'));
        $this->assertSame($plugin2, $collector->getByPlugin('plugin2'));
        $this->assertNull($collector->getByPlugin('unknown'));
    }

    public function testGetServiceCount(): void
    {
        $plugin1 = $this->createMock(PluginInterface::class);
        $plugin2 = $this->createMock(PluginInterface::class);
        $collector = new VisPluginCollector([$plugin1, $plugin2]);
        $this->assertEquals(2, $collector->getServiceCount());
    }
}
