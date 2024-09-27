<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use JBSNewMedia\VisBundle\Plugin\PluginInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @template T of PluginInterface
 */
class PluginManager
{
    /**
     * @param ServiceLocator<T> $pluginLocator
     */
    public function __construct(protected readonly ServiceLocator $pluginLocator)
    {
    }

    public function initPlugins(): void
    {
        foreach ($this->pluginLocator->getProvidedServices() as $serviceId => $serviceClass) {
            $plugin = $this->pluginLocator->get($serviceId);
            if ($plugin instanceof PluginInterface) {
                $plugin->init();
            }
            if ($plugin instanceof PluginInterface) {
                $plugin->setNavigation();
            }
        }
    }
}
