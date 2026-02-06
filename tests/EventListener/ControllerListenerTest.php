<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\EventListener;

use JBSNewMedia\VisBundle\Controller\VisAbstractController;
use JBSNewMedia\VisBundle\EventListener\ControllerListener;
use JBSNewMedia\VisBundle\Service\Vis;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ControllerListenerTest extends TestCase
{
    public function testOnKernelControllerWithVisAbstractController(): void
    {
        $vis = $this->createMock(Vis::class);
        $security = $this->createMock(Security::class);
        $listener = new ControllerListener($vis, $security);

        $controller = new class extends VisAbstractController {
            public function someMethod(): void {}
        };

        $request = new Request();
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [$controller, 'someMethod'],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelController($event);
        // Momentan macht der Listener nichts sichtbares im Code,
        // aber wir stellen sicher, dass er ohne Fehler durchläuft.
        $this->assertTrue(true);
    }

    public function testOnKernelControllerWithOtherController(): void
    {
        $vis = $this->createMock(Vis::class);
        $security = $this->createMock(Security::class);
        $listener = new ControllerListener($vis, $security);

        $controller = function() {};

        $request = new Request();
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelController($event);
        $this->assertTrue(true);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = ControllerListener::getSubscribedEvents();
        $this->assertArrayHasKey('kernel.controller', $events);
        $this->assertEquals('onKernelController', $events['kernel.controller']);
    }
}
