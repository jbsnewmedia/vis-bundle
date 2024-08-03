<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\EventListener;

use JBSNewMedia\VisBundle\Plugin\PluginInterface;
use JBSNewMedia\VisBundle\Service\PluginManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @template T of PluginInterface
 */
class PluginListener
{
    /**
     * @param PluginManager<T> $pluginManager
     */
    public function __construct(protected PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->pluginManager->initPlugins();
    }
}
