<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Plugin;

use JBSNewMedia\VisBundle\Attribute\VisPlugin;

abstract class AbstractPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    /**
     * Returns the plugin identifier defined via #[VisPlugin(plugin: '...')]
     */
    protected function getPluginId(): ?string
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

    public function init(): void
    {
    }

    public function setNavigation(): void
    {
    }

    public function setTopBar(): void
    {
    }
}
