<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core;

use JBSNewMedia\VisBundle\Core\KernelPluginCollection;
use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use PHPUnit\Framework\TestCase;

class KernelPluginCollectionTest extends TestCase
{
    public function testAddAndGet(): void
    {
        $collection = new KernelPluginCollection();
        $plugin = $this->createMock(AbstractVisBundle::class);
        $pluginClass = $plugin::class;

        $collection->add($plugin);
        $this->assertTrue($collection->has($pluginClass));
        $this->assertSame($plugin, $collection->get($pluginClass));
        $this->assertCount(1, $collection->all());
    }

    public function testAddDuplicate(): void
    {
        $collection = new KernelPluginCollection();
        $plugin = $this->createMock(AbstractVisBundle::class);

        $collection->add($plugin);
        $collection->add($plugin);

        $this->assertCount(1, $collection->all());
    }

    public function testAddList(): void
    {
        $collection = new KernelPluginCollection();
        $plugin1 = $this->createMock(AbstractVisBundle::class);
        $plugin2 = $this->getMockBuilder(AbstractVisBundle::class)
            ->setMockClassName('Plugin2Collection')
            ->getMock();

        $collection->addList([$plugin1, $plugin2]);
        $this->assertCount(2, $collection->all());
    }

    public function testGetActives(): void
    {
        $activePlugin = $this->createMock(AbstractVisBundle::class);
        $activePlugin->method('isActive')->willReturn(true);

        $inactivePlugin = $this->getMockBuilder(AbstractVisBundle::class)
            ->setMockClassName('InactivePlugin')
            ->getMock();
        $inactivePlugin->method('isActive')->willReturn(false);

        $collection = new KernelPluginCollection([$activePlugin, $inactivePlugin]);

        $actives = $collection->getActives();
        $this->assertCount(1, $actives);
        $this->assertContains($activePlugin, $actives);
    }

    public function testFilter(): void
    {
        $plugin1 = $this->createMock(AbstractVisBundle::class);
        $plugin2 = $this->getMockBuilder(AbstractVisBundle::class)
            ->setMockClassName('Plugin2Filter')
            ->getMock();

        $collection = new KernelPluginCollection([$plugin1::class => $plugin1, $plugin2::class => $plugin2]);

        $filtered = $collection->filter(fn($p) => $p === $plugin1);

        $this->assertCount(1, $filtered->all());
        $this->assertTrue($filtered->has($plugin1::class));
    }
}
