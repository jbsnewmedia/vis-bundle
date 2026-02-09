<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Service;

use JBSNewMedia\VisBundle\Service\PluginManager;
use JBSNewMedia\VisBundle\Service\VisPluginCollector;
use PHPUnit\Framework\TestCase;

class PluginManagerTest extends TestCase
{
    public function testInitPlugins(): void
    {
        $collector = $this->createMock(VisPluginCollector::class);
        $collector->expects($this->once())
            ->method('processAll');

        $manager = new PluginManager($collector);
        $manager->initPlugins();
    }
}
