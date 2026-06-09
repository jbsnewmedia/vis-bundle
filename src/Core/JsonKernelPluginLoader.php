<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Core;

use Composer\Autoload\ClassLoader;
use Symfony\Component\HttpKernel\KernelInterface;

class JsonKernelPluginLoader extends KernelPluginLoader
{
    protected PluginService $pluginService;

    public function __construct(ClassLoader $classLoader, KernelInterface $appKernel, ?string $pluginDir = null)
    {
        parent::__construct($classLoader, $pluginDir);
        $this->pluginService = new PluginService($appKernel, $this->getPluginDir($appKernel->getProjectDir()));
    }

    protected function loadPluginInfos(): void
    {
        foreach ($this->pluginService->loadPluginsInfoFromJson() as $plugin) {
            $this->pluginInfos[] = $plugin;
        }
    }
}
