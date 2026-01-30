<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class VisExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $locales = $config['locales'] ?? ['en'];
        if ([] === $locales) {
            $locales = [$config['default_locale'] ?? 'en'];
        }

        $container->setParameter('vis.locales', $locales);
        $container->setParameter('vis.default_locale', $config['default_locale'] ?? 'en');

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }
}
