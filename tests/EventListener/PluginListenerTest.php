<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\EventListener;

use JBSNewMedia\VisBundle\EventListener\PluginListener;
use JBSNewMedia\VisBundle\Service\PluginManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PluginListenerTest extends TestCase
{
    public function testOnKernelRequest(): void
    {
        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager->expects($this->once())->method('initPlugins');

        $listener = new PluginListener($pluginManager);
        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
    }

    public function testGetSubscribedEvents(): array
    {
        $events = PluginListener::getSubscribedEvents();
        $this->assertArrayHasKey('kernel.request', $events);
        $this->assertEquals('onKernelRequest', $events['kernel.request']);

        return $events;
    }
}
