<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests;

use JBSNewMedia\VisBundle\VisBundle;
use JBSNewMedia\VisBundle\DependencyInjection\VisExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class VisBundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new VisBundle();
        $this->assertInstanceOf(VisExtension::class, $bundle->getContainerExtension());
    }

    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        $bundle = new VisBundle();
        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $found = false;
        foreach ($passes as $pass) {
            if ($pass instanceof \JBSNewMedia\VisBundle\DependencyInjection\Compiler\VisPluginPass) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'VisPluginPass was not added to container.');
    }

    public function testGetPath(): void
    {
        $bundle = new VisBundle();
        $this->assertTrue(is_dir($bundle->getPath()));
        $this->assertTrue(file_exists($bundle->getPath() . '/src/VisBundle.php'));
    }

    public function testGetContainerExtensionNull(): void
    {
        $bundle = new class extends VisBundle {
            public function setExtension($ext): void
            {
                $this->extension = $ext;
            }
        };
        $bundle->setExtension(false);
        $this->assertNull($bundle->getContainerExtension());
    }
}
