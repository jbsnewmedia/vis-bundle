<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\EventListener;

use JBSNewMedia\VisBundle\Controller\VisAbstractController;
use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerListener implements EventSubscriberInterface
{
    public function __construct(protected Vis $vis, protected Security $security)
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller) && $controller[0] instanceof VisAbstractController) {
            // placeholder for future use
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
