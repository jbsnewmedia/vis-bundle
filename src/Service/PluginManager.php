<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use JBSNewMedia\VisBundle\Plugin\PluginInterface;

/**
 * @template T of PluginInterface
 */
class PluginManager
{
    public function __construct(
        protected readonly VisPluginCollector $visPluginCollector,
    ) {
    }

    public function initPlugins(): void
    {
        $this->visPluginCollector->processAll();
    }
}
