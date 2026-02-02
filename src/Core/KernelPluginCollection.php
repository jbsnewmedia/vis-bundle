<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Core;

use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;

class KernelPluginCollection
{
    /**
     * @param AbstractVisBundle[] $plugins
     */
    public function __construct(private array $plugins = [])
    {
    }

    public function add(AbstractVisBundle $plugin): void
    {
        $class = $plugin::class;
        if ($this->has($class)) {
            return;
        }
        $this->plugins[$class] = $plugin;
    }

    /**
     * @param AbstractVisBundle[] $plugins
     */
    public function addList(array $plugins): void
    {
        foreach ($plugins as $plugin) {
            $this->add($plugin);
        }
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->plugins);
    }

    public function get(string $name): ?AbstractVisBundle
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * @return AbstractVisBundle[]
     */
    public function all(): array
    {
        return $this->plugins;
    }

    /**
     * @return AbstractVisBundle[]
     */
    public function getActives(): array
    {
        return array_filter($this->plugins, static fn (AbstractVisBundle $plugin): bool => method_exists($plugin, 'isActive') ? $plugin->isActive() : false);
    }

    public function filter(\Closure $closure): KernelPluginCollection
    {
        return new self(array_filter($this->plugins, $closure));
    }
}
