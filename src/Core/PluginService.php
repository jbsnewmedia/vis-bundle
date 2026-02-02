<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Core;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class PluginService
{
    protected string $projectDir;
    protected string $environment;
    protected KernelInterface $appKernel;

    public function __construct(KernelInterface $appKernel)
    {
        $this->projectDir = $appKernel->getProjectDir();
        $this->environment = $appKernel->getEnvironment();
        $this->appKernel = $appKernel;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws \JsonException
     */
    public function loadPluginsInfoFromJson(?string $pluginFilter = null): array
    {
        $plugins = [];
        $pluginsPath = $this->projectDir.'/plugins/plugins.json';
        if (file_exists($pluginsPath)) {
            $json = file_get_contents($pluginsPath);
            if (false !== $json) {
                $pluginData = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
                if (is_array($pluginData)) {
                    foreach ($pluginData as $plugin) {
                        if (is_array($plugin) && (null === $pluginFilter || ($plugin['name'] ?? null) === $pluginFilter)) {
                            $plugins[] = $plugin;
                        }
                    }
                }
            }
        }

        return $plugins;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws \JsonException
     */
    public function getPluginData(string $pluginName): ?array
    {
        $plugins = $this->loadPluginsInfoFromJson($pluginName);
        if (\count($plugins) > 0) {
            return array_pop($plugins);
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws \JsonException
     */
    public function loadFromPluginPath(): array
    {
        $plugins = $this->loadPluginsInfoFromJson();
        $return = [];
        $pluginDirs = glob($this->projectDir.'/plugins/*');
        if (false === $pluginDirs) {
            return $return;
        }
        $dirs = array_filter($pluginDirs, 'is_dir');
        foreach ($dirs as $dir) {
            $pluginName = basename((string) $dir);
            $active = false;
            foreach ($plugins as $plugin) {
                if (is_array($plugin) && ($plugin['name'] ?? null) === $pluginName) {
                    $active = $plugin['active'] ?? false;
                    break;
                }
            }
            $pluginData = $this->createPluginData($pluginName);
            if (is_array($pluginData)) {
                $pluginData['active'] = $active;
                $return[] = $pluginData;
            }
        }

        return $return;
    }

    public function enablePlugin(string $pluginName): bool
    {
        $pluginData = $this->createPluginData($pluginName);
        if (is_array($pluginData)) {
            $pluginsPublicFolder = null;
            $publicFolder = $this->projectDir.'/plugins/'.$pluginName.'/public';
            if (file_exists($publicFolder) && is_dir($publicFolder)) {
                $pluginsPublicFolder = $publicFolder;
            }
            $pluginData['active'] = true;
            $pluginData['public'] = $pluginsPublicFolder;

            $this->storePlugins($pluginData);
            $this->copyPluginsPublicFolder($pluginData);
            exec($this->projectDir.'/bin/console cache:clear --env='.$this->environment);
            $this->pluginActivationLifecycle($pluginData);

            return true;
        }

        return false;
    }

    /**
     * @param array<string|mixed> $pluginData
     */
    public function pluginActivationLifecycle(array $pluginData): void
    {
        $this->runPluginLifecycle($pluginData);
    }

    /**
     * @param array<string|mixed> $pluginData
     */
    public function pluginUpdateLifecycle(array $pluginData): void
    {
        $this->runPluginLifecycle($pluginData);
    }

    /**
     * @param array<string|mixed> $pluginData
     */
    private function runPluginLifecycle(array $pluginData): void
    {
        $className = $pluginData['baseClass'] ?? null;
        if (!is_string($className) || !class_exists($className)) {
            return;
        }
        if (empty($pluginData['active'])) {
            return;
        }
        $plugin = new $className(true, $pluginData['path'] ?? '', $this->projectDir);
        if (method_exists($plugin, 'activate')) {
            $plugin->activate(new PluginInstallContext($this->appKernel->getContainer(), $pluginData));
        }
    }

    /**
     * @param array<string, mixed> $pluginData
     */
    public function copyPluginsPublicFolder(array $pluginData): void
    {
        if (!empty($pluginData['public']) && is_string($pluginData['public'])) {
            $src = $pluginData['public'];
            $dest = $this->projectDir.'/public/bundles/'.$pluginData['name'];
            $filesystem = new Filesystem();
            $filesystem->mkdir($dest, 0777);
            $filesystem->mirror($src, $dest, null, ['override' => true, 'delete' => true]);
        }
    }

    public function disablePlugin(string $pluginName): bool
    {
        $pluginData = $this->createPluginData($pluginName);
        if (is_array($pluginData)) {
            $pluginData['active'] = false;
            $this->storePlugins($pluginData);
            $filesystem = new Filesystem();
            $filesystem->remove($this->projectDir.'/public/bundles/'.$pluginData['name']);
            exec($this->projectDir.'/bin/console cache:clear --env='.$this->environment);

            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws \JsonException
     */
    private function createPluginData(string $pluginName): ?array
    {
        $composerData = $this->loadPluginsComposer($this->projectDir.'/plugins/'.$pluginName);
        if (
            is_array($composerData)
            && isset($composerData['extra'])
            && is_array($composerData['extra'])
            && isset($composerData['extra']['amicron-platform-plugin-class'])
        ) {
            return [
                'path' => 'plugins/'.$pluginName,
                'baseClass' => $composerData['extra']['amicron-platform-plugin-class'],
                'name' => $pluginName,
                'label' => $composerData['extra']['label'] ?? '',
                'description' => $composerData['extra']['description'] ?? '',
                'managedByComposer' => false,
                'autoload' => $composerData['autoload'] ?? null,
                'extra' => $composerData['extra'],
            ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws \JsonException
     */
    private function loadPluginsComposer(string $path): ?array
    {
        if (file_exists($path.'/composer.json')) {
            $json = file_get_contents($path.'/composer.json');
            if (false === $json) {
                return null;
            }
            $composerData = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
            if (is_array($composerData)) {
                return $composerData;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $pluginUpdateData
     *
     * @throws \JsonException
     */
    private function storePlugins(array $pluginUpdateData): void
    {
        $plugins = $this->loadPluginsInfoFromJson();
        $found = false;
        foreach ($plugins as $index => $plugin) {
            if (is_array($plugin) && ($plugin['name'] ?? null) === $pluginUpdateData['name']) {
                $found = $index;
                break;
            }
        }
        if (false === $found) {
            $plugins[] = $pluginUpdateData;
        } else {
            $plugins[$found] = $pluginUpdateData;
        }
        file_put_contents(
            $this->projectDir.'/plugins/plugins.json',
            json_encode($plugins, \JSON_PRETTY_PRINT)
        );
    }
}
