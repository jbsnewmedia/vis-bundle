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

    public function activate(PluginInstallContext $context):void
    {

    }

    public function update(PluginInstallContext $context):void
    {

    }
}
