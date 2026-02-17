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

        $locales = (array) ($config['locales'] ?? ['en']);
        if ([] === $locales) {
            $defaultLocale = $config['default_locale'] ?? 'en';
            $locales = [is_scalar($defaultLocale) ? (string) $defaultLocale : 'en'];
        }

        $container->setParameter('vis.locales', $locales);
        $defaultLocale = $config['default_locale'] ?? 'en';
        $container->setParameter('vis.default_locale', is_scalar($defaultLocale) ? (string) $defaultLocale : 'en');

        $topbarConfig = $config['topbar'] ?? [];
        $container->setParameter('vis.topbar.darkmode', $topbarConfig['darkmode'] ?? true);
        $container->setParameter('vis.topbar.locale', $topbarConfig['locale'] ?? true);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }
}
