<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Core;

use Composer\Autoload\ClassLoader;
use Composer\ClassMapGenerator\ClassMapGenerator;
use JBSNewMedia\VisBundle\Core\Exception\KernelPluginLoaderException;
use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class KernelPluginLoader extends Bundle
{
    private string $pluginDir = 'plugins';

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $pluginInfos = [];

    private bool $initialized = false;

    private readonly KernelPluginCollection $pluginInstances;

    public function __construct(private readonly ClassLoader $classLoader)
    {
        $this->pluginInstances = new KernelPluginCollection();
    }

    final public function getPluginDir(string $projectDir): string
    {
        if (0 === mb_strpos($this->pluginDir, '/')) {
            return $this->pluginDir;
        }

        return $projectDir.'/'.$this->pluginDir;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    final public function getPluginInfos(): array
    {
        return $this->pluginInfos;
    }

    final public function getPluginInstances(): KernelPluginCollection
    {
        return $this->pluginInstances;
    }

    /**
     * @param array<string, mixed> $kernelParameters
     * @param string[]             $loadedBundles
     *
     * @return iterable<Bundle>
     */
    final public function getBundles(array $kernelParameters = [], array $loadedBundles = []): iterable
    {
        if (!$this->initialized) {
            return;
        }

        foreach ($this->pluginInstances->getActives() as $plugin) {
            if (!in_array($plugin->getName(), $loadedBundles, true)) {
                yield $plugin;
                $loadedBundles[] = $plugin->getName();
            }
        }

        if (!in_array($this->getName(), $loadedBundles, true)) {
            yield $this;
        }
    }

    final public function initializePlugins(string $projectDir): void
    {
        if ($this->initialized) {
            return;
        }

        $this->loadPluginInfos();
        if (empty($this->pluginInfos)) {
            $this->initialized = true;

            return;
        }

        $this->registerPluginNamespaces($projectDir);
        $this->instantiatePlugins($projectDir);

        $this->initialized = true;
    }

    final public function build(ContainerBuilder $container): void
    {
        if (!$this->initialized) {
            return;
        }
        parent::build($container);

        foreach ($this->pluginInstances->getActives() as $plugin) {
            $class = $plugin::class;
            $definition = new Definition();
            if ($container->hasDefinition($class)) {
                $definition = $container->getDefinition($class);
            }
            $definition->addArgument($class);
            $definition->setAutowired(true);
            $definition->setPublic(true);
            $container->setDefinition($class, $definition);
        }
    }

    final public function getPluginInstance(string $class): ?AbstractVisBundle
    {
        $plugin = $this->pluginInstances->get($class);
        if (!$plugin || !(method_exists($plugin, 'isActive') && $plugin->isActive())) {
            return null;
        }

        return $plugin;
    }

    public function getClassLoader(): ClassLoader
    {
        return $this->classLoader;
    }

    abstract protected function loadPluginInfos(): void;

    private function registerPluginNamespaces(string $projectDir): void
    {
        foreach ($this->pluginInfos as $plugin) {
            if (!is_array($plugin)) {
                continue;
            }
            $pluginName = '';
            if (isset($plugin['name']) && is_string($plugin['name'])) {
                $pluginName = $plugin['name'];
            } elseif (isset($plugin['baseClass']) && is_string($plugin['baseClass'])) {
                $pluginName = $plugin['baseClass'];
            }

            if (!isset($plugin['autoload']) || !is_array($plugin['autoload'])) {
                $reason = sprintf(
                    'Unable to register plugin "%s" in autoload. Required property `autoload` missing.',
                    $pluginName
                );
                throw new KernelPluginLoaderException($pluginName, $reason);
            }

            $psr4 = isset($plugin['autoload']['psr-4']) && is_array($plugin['autoload']['psr-4'])
                ? $plugin['autoload']['psr-4']
                : [];

            $psr0 = isset($plugin['autoload']['psr-0']) && is_array($plugin['autoload']['psr-0'])
                ? $plugin['autoload']['psr-0']
                : [];

            if (empty($psr4) && empty($psr0)) {
                $reason = sprintf(
                    'Unable to register plugin "%s" in autoload. Required property `psr-4` or `psr-0` missing in property autoload.',
                    $pluginName
                );
                throw new KernelPluginLoaderException($pluginName, $reason);
            }

            foreach ((array) $psr4 as $namespace => $paths) {
                $paths = is_array($paths) ? $paths : [$paths];
                $pluginPath = isset($plugin['path']) && is_string($plugin['path']) ? $plugin['path'] : '';
                $mappedPaths = $this->mapPsrPaths(
                    $pluginName, $paths,
                    $projectDir,
                    $pluginPath
                );
                $this->classLoader->addPsr4($namespace, $mappedPaths);
                if ($this->classLoader->isClassMapAuthoritative()) {
                    foreach ($mappedPaths as $mappedPath) {
                        $this->classLoader->addClassMap(ClassMapGenerator::createMap($mappedPath));
                    }
                }
            }

            foreach ((array) $psr0 as $namespace => $paths) {
                $paths = is_array($paths) ? $paths : [$paths];
                $pluginPath = isset($plugin['path']) && is_string($plugin['path']) ? $plugin['path'] : '';
                $mappedPaths = $this->mapPsrPaths(
                    $pluginName, $paths,
                    $projectDir,
                    $pluginPath
                );
                $this->classLoader->add($namespace, $mappedPaths);
                if ($this->classLoader->isClassMapAuthoritative()) {
                    foreach ($mappedPaths as $mappedPath) {
                        $this->classLoader->addClassMap(ClassMapGenerator::createMap($mappedPath));
                    }
                }
            }
        }
    }

    /**
     * @param string[] $psr
     *
     * @return list<string>
     */
    private function mapPsrPaths(string $plugin, array $psr, string $projectDir, string $pluginRootPath): array
    {
        $mappedPaths = [];
        $absolutePluginRootPath = $this->getAbsolutePluginRootPath($projectDir, $pluginRootPath);

        if (0 !== mb_strpos($absolutePluginRootPath, $projectDir)) {
            throw new KernelPluginLoaderException($plugin, sprintf('Plugin dir %s needs to be a sub-directory of the project dir %s', $pluginRootPath, $projectDir));
        }

        foreach ($psr as $path) {
            $mappedPaths[] = $absolutePluginRootPath.'/'.$path;
        }

        return $mappedPaths;
    }

    private function getAbsolutePluginRootPath(string $projectDir, string $pluginRootPath): string
    {
        if (0 !== mb_strpos($pluginRootPath, '/')) {
            $pluginRootPath = $projectDir.'/'.$pluginRootPath;
        }

        return $pluginRootPath;
    }

    private function instantiatePlugins(string $projectDir): void
    {
        foreach ($this->pluginInfos as $pluginData) {
            if (!is_array($pluginData) || !isset($pluginData['baseClass']) || !is_string($pluginData['baseClass'])) {
                continue;
            }
            $className = $pluginData['baseClass'];

            $pluginClassFilePath = $this->classLoader->findFile($className);
            if (!class_exists($className) || !$pluginClassFilePath || !file_exists($pluginClassFilePath)) {
                continue;
            }

            // 'active' kann bool oder int oder string oder gar nicht gesetzt sein.
            $isActive = !empty($pluginData['active']);
            $pluginPath = isset($pluginData['path']) && is_string($pluginData['path']) ? $pluginData['path'] : '';

            $plugin = new $className($isActive, $pluginPath, $projectDir);

            if (!$plugin instanceof AbstractVisBundle) {
                $reason = sprintf(
                    'Plugin class "%s" must extend "%s"',
                    $plugin::class,
                    AbstractVisBundle::class
                );
                $pluginName = isset($pluginData['name']) && is_string($pluginData['name'])
                    ? $pluginData['name']
                    : $className;
                throw new KernelPluginLoaderException($pluginName, $reason);
            }

            $this->pluginInstances->add($plugin);
        }
    }
}
