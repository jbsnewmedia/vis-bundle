<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\DependencyInjection\Compiler;

use JBSNewMedia\VisBundle\Attribute\VisPlugin;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class VisPluginPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if (!$class || !class_exists($class)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($class);
                $attributes = $reflection->getAttributes(VisPlugin::class);

                foreach ($attributes as $attribute) {
                    $instance = $attribute->newInstance();

                    $tagAttributes = [];
                    if (!empty($instance->plugin)) {
                        $tagAttributes['plugin'] = $instance->plugin;
                    }

                    $definition->addTag('VisPlugin', $tagAttributes);
                }
            } catch (\ReflectionException) {
                // Class not found or not accessible
                continue;
            }
        }
    }
}
