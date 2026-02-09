<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\EventListener;

use JBSNewMedia\VisBundle\EventListener\LocaleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LocaleListenerTest extends TestCase
{
    public function testOnKernelRequestWithNoSession(): void
    {
        $listener = new LocaleListener();
        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
        $this->assertEquals('en', $request->getLocale());
    }

    public function testOnKernelRequestWithSessionLocale(): void
    {
        $listener = new LocaleListener();
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set('_locale', 'de');
        $request->setSession($session);

        // Simuliere hasPreviousSession
        $request->cookies->set($session->getName(), '1');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
        $this->assertEquals('de', $request->getLocale());
    }

    public function testOnKernelRequestWithEmptySessionLocale(): void
    {
        $listener = new LocaleListener();
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set('_locale', '');
        $request->setSession($session);
        $request->cookies->set($session->getName(), '1');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
        $this->assertEquals('en', $request->getLocale());
    }
}
