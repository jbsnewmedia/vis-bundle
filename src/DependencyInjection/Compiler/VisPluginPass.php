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

            foreach ($this->getAttributes($class) as $instance) {
                $tagAttributes = [];
                if (!empty($instance->plugin)) {
                    $tagAttributes['plugin'] = $instance->plugin;
                }
                $tagAttributes['priority'] = $instance->priority ?? 100;

                $definition->addTag('VisPlugin', $tagAttributes);
            }
        }
    }

    /**
     * @param class-string $class
     *
     * @return VisPlugin[]
     */
    protected function getAttributes(string $class): array
    {
        try {
            /** @var class-string $class */
            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(VisPlugin::class);
            $instances = [];

            foreach ($attributes as $attribute) {
                $instances[] = $attribute->newInstance();
            }

            return $instances;
        } catch (\ReflectionException) {
            return [];
        }
    }
}
