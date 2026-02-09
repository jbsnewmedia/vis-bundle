<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Plugin;

use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Config\FileLocator;

class AbstractVisBundleTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/vis_bundle_test_' . uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testIsActive(): void
    {
        $bundle = new class(true) extends AbstractVisBundle {};
        $this->assertTrue($bundle->isActive());

        $bundleInactive = new class(false) extends AbstractVisBundle {};
        $this->assertFalse($bundleInactive->isActive());
    }

    public function testGetBasePath(): void
    {
        $bundle = new class(true, $this->tempDir) extends AbstractVisBundle {};
        $this->assertEquals($this->tempDir, $bundle->getBasePath());
    }

    public function testBuildInactiveDoesNothing(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->never())->method('getExtensions');

        $bundle = new class(false) extends AbstractVisBundle {};
        $bundle->build($container);
    }

    public function testConfigureRoutesInactiveDoesNothing(): void
    {
        $collection = new RouteCollection();
        $loader = $this->createMock(PhpFileLoader::class);
        $loader->expects($this->never())->method('import');
        $routes = new RoutingConfigurator($collection, $loader, 'path', 'file');

        $bundle = new class(false) extends AbstractVisBundle {};
        $bundle->configureRoutes($routes, 'test');
    }

    public function testConfigureRoutesActiveImportsControllers(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/src/Controller');

        $bundle = new class(true, $this->tempDir) extends AbstractVisBundle {
            public function getPath(): string {
                return $this->getBasePath();
            }
        };

        $collection = new RouteCollection();
        $loader = $this->createMock(PhpFileLoader::class);
        $loader->expects($this->once())
            ->method('import')
            ->with($this->tempDir . '/src/Controller', 'attribute');

        $routes = new RoutingConfigurator($collection, $loader, 'path', 'file');

        $bundle->configureRoutes($routes, 'test');
    }

    public function testBuildActiveRegistersServices(): void
    {
        $configDir = $this->tempDir . '/Resources/config';
        $this->filesystem->mkdir($configDir);
        $this->filesystem->dumpFile($configDir . '/services.yaml', 'services:');

        $bundle = new class(true, $this->tempDir) extends AbstractVisBundle {
            public function getPath(): string {
                return $this->getBasePath();
            }
        };

        $container = new ContainerBuilder();
        $bundle->build($container);

        $this->assertTrue(true);
    }

    public function testBuildActivePrependsTwigPath(): void
    {
        $baseDir = __DIR__ . '/temp_' . uniqid();
        $this->filesystem->mkdir($baseDir . '/src/Resources/view');

        $bundle = new class(true, $baseDir) extends AbstractVisBundle {
            public function getPath(): string {
                return $this->getBasePath();
            }
        };

        $container = $this->createMock(ContainerBuilder::class);
        $container->method('getExtensions')->willReturn(['twig' => 'some_extension']);

        $container->expects($this->once())
            ->method('prependExtensionConfig')
            ->with('twig', [
                'paths' => [
                    $baseDir.'/Resources/view' => $bundle->getName(),
                ],
            ]);

        try {
            $bundle->build($container);
        } finally {
            $this->filesystem->remove($baseDir);
        }
    }

    public function testBuildActiveLoadsServiceFiles(): void
    {
        $configDir = $this->tempDir . '/Resources/config';
        $this->filesystem->mkdir($configDir);
        $this->filesystem->dumpFile($configDir . '/services.yaml', 'services: { test_service: { class: stdClass } }');
        $this->filesystem->dumpFile($configDir . '/services.xml', '<?xml version="1.0" encoding="UTF-8" ?><container xmlns="http://symfony.com/schema/dic/services"></container>');

        $bundle = new class(true, $this->tempDir) extends AbstractVisBundle {
            public function getPath(): string {
                return $this->getBasePath();
            }
        };

        $container = new ContainerBuilder();
        $bundle->build($container);

        $this->assertTrue($container->has('test_service'));
    }

    public function testConstructorWithProjectDir(): void
    {
        $projectDir = '/tmp/project';
        $relativeBasePath = 'plugins/my-plugin';

        // basePath does not start with /
        $bundle = new class(true, $relativeBasePath, $projectDir) extends AbstractVisBundle {};
        $this->assertEquals($projectDir . '/' . $relativeBasePath, $bundle->getBasePath());
    }

    public function testConstructorWithAbsoluteBasePath(): void
    {
        $projectDir = '/tmp/project';
        $absoluteBasePath = '/opt/plugins/my-plugin';

        // basePath starts with /
        $bundle = new class(true, $absoluteBasePath, $projectDir) extends AbstractVisBundle {};
        $this->assertEquals($absoluteBasePath, $bundle->getBasePath());
    }
}
