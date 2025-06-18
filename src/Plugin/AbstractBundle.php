<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Plugin;

use JBSNewMedia\Vis\Core\PluginInstallContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

abstract class AbstractBundle extends \Symfony\Component\HttpKernel\Bundle\AbstractBundle
{
    final public function __construct(private readonly bool $active, private string $basePath, ?string $projectDir = null)
    {
        if ($projectDir && mb_strpos($this->basePath, '/') !== 0) {
            $this->basePath = $projectDir . '/' . $this->basePath;
        }

        $this->path = $this->computePluginClassPath();
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function build(ContainerBuilder $container): void
    {
        $this->registerContainerFile($container);
    }

    private function registerContainerFile(ContainerBuilder $container): void
    {
        if (!$this->isActive()) {
            return;
        }
        /*
         * Register Plugin Template Path
         */
        if (isset($container->getExtensions()['twig'])) {
            if (file_exists($this->getBasePath() . '/src/Resources/view')) {
                $container->prependExtensionConfig('twig', [
                    'paths' => [
                        $this->getPath() . '/Resources/view' => $this->getName(),
                    ],
                ]);
            }
        }
        $fileLocator = new FileLocator($this->getPath());
        $loaderResolver = new LoaderResolver([
            new XmlFileLoader($container, $fileLocator),
            new YamlFileLoader($container, $fileLocator),
            new PhpFileLoader($container, $fileLocator),
        ]);

        /**
         * Services der Plugins laden.
         */
        $delegatingLoader = new DelegatingLoader($loaderResolver);
        $serviceFiles = glob($this->getPath() . '/Resources/config/services.*');
        if ($serviceFiles !== false) {
            foreach ($serviceFiles as $path) {
                $delegatingLoader->load($path);
            }
        }
    }

    public function configureRoutes(RoutingConfigurator $routes, string $environment): void
    {
        if (!$this->isActive()) {
            return;
        }
        $routes->import($this->getPath() . '/Controller', 'attribute');
    }

    public function rebuildContainer(): bool
    {
        return true;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    private function computePluginClassPath(): string
    {
        $canonicalizedPluginClassPath = parent::getPath();
        $canonicalizedPluginPath = realpath($this->basePath);

        if ($canonicalizedPluginPath !== false && mb_strpos($canonicalizedPluginClassPath, $canonicalizedPluginPath) === 0) {
            $relativePluginClassPath = mb_substr($canonicalizedPluginClassPath, mb_strlen($canonicalizedPluginPath));

            return $this->basePath . $relativePluginClassPath;
        }

        return parent::getPath();
    }

    public function activate(PluginInstallContext $context):void
    {

    }

    public function update(PluginInstallContext $context):void
    {

    }
}
