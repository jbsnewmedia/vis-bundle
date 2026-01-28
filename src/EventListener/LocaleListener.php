<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class LocaleListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($locale = $request->getSession()->get('_locale')) {
            $request->setLocale($locale);
        }
    }
}
