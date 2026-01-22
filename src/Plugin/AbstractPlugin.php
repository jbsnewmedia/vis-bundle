<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Plugin;

use JBSNewMedia\VisBundle\Attribute\VisPlugin;
use JBSNewMedia\VisBundle\Model\Tool;

abstract class AbstractPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getPluginId(): ?string
    {
        try {
            $reflection = new \ReflectionClass(static::class);
            $attributes = $reflection->getAttributes(VisPlugin::class);
            foreach ($attributes as $attribute) {
                /** @var VisPlugin $instance */
                $instance = $attribute->newInstance();
                if (!empty($instance->plugin)) {
                    return $instance->plugin;
                }
            }
        } catch (\ReflectionException) {
            // ignore
        }

        return null;
    }

    public function getPriority(): int
    {
        try {
            $reflection = new \ReflectionClass(static::class);
            $attributes = $reflection->getAttributes(VisPlugin::class);
            foreach ($attributes as $attribute) {
                /** @var VisPlugin $instance */
                $instance = $attribute->newInstance();

                return $instance->priority;
            }
        } catch (\ReflectionException) {
            // ignore
        }

        return 100;
    }

    public function createTool(): Tool
    {
        return new Tool((string) $this->getPluginId(), (int) $this->getPriority());
    }

    public function init(): void
    {
    }

    public function setTopBar(): void
    {
    }

    public function setNavigation(): void
    {
    }
}
