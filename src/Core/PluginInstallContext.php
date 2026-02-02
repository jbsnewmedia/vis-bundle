<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Core;

use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginInstallContext
{
    /**
     * @param array<string|mixed> $pluginData
     */
    public function __construct(private readonly ContainerInterface $container, private readonly array $pluginData)
    {
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return array<string|mixed>
     */
    public function getPluginData(): array
    {
        return $this->pluginData;
    }
}
