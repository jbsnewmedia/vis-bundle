<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle;

use JBSNewMedia\VisBundle\DependencyInjection\Compiler\VisPluginPass;
use JBSNewMedia\VisBundle\DependencyInjection\VisExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class VisBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new VisExtension();
        }

        if (false === $this->extension) {
            return null;
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new VisPluginPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
