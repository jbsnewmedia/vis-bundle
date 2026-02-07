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
        foreach ($this->getAttributes() as $instance) {
            if (!empty($instance->plugin)) {
                return $instance->plugin;
            }
        }

        return null;
    }

    public function getPriority(): int
    {
        foreach ($this->getAttributes() as $instance) {
            return $instance->priority;
        }

        return 100;
    }

    /**
     * @return VisPlugin[]
     */
    protected function getAttributes(): array
    {
        $reflection = new \ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(VisPlugin::class);
        $instances = [];

        foreach ($attributes as $attribute) {
            $instances[] = $attribute->newInstance();
        }

        return $instances;
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
