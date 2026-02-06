<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core;

use JBSNewMedia\VisBundle\Core\PluginInstallContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginInstallContextTest extends TestCase
{
    public function testGetters(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $pluginData = ['name' => 'test-plugin'];
        $context = new PluginInstallContext($container, $pluginData);

        $this->assertSame($container, $context->getContainer());
        $this->assertSame($pluginData, $context->getPluginData());
    }
}
