<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\EventListener;

use JBSNewMedia\VisBundle\Plugin\PluginInterface;
use JBSNewMedia\VisBundle\Service\PluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @template T of PluginInterface
 */
class PluginListener implements EventSubscriberInterface
{
    /**
     * @param PluginManager<T> $pluginManager
     */
    public function __construct(protected PluginManager $pluginManager)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->pluginManager->initPlugins();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
