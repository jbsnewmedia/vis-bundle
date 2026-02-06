<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core;

use Composer\Autoload\ClassLoader;
use JBSNewMedia\VisBundle\Core\KernelPluginLoader;
use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class KernelPluginLoaderCoverageTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private ClassLoader $classLoader;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_kernel_loader_coverage_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);
        $this->classLoader = $this->createMock(ClassLoader::class);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testInstantiatePluginsNoBaseClass(): void
    {
        $loader = new class($this->classLoader) extends KernelPluginLoader {
            protected function loadPluginInfos(): void {
                $this->pluginInfos = [['name' => 'NoBaseClass']];
            }
        };

        $ref = new \ReflectionMethod(KernelPluginLoader::class, 'instantiatePlugins');
        $ref->setAccessible(true);
        $ref->invoke($loader, $this->tempDir);

        $this->assertEmpty($loader->getPluginInstances()->all());
    }

    public function testInstantiatePluginsClassNotFound(): void
    {
        $loader = new class($this->classLoader) extends KernelPluginLoader {
            protected function loadPluginInfos(): void {
                $this->pluginInfos = [['name' => 'NonExistent', 'baseClass' => 'NonExistent\\Class']];
            }
        };

        $ref = new \ReflectionMethod(KernelPluginLoader::class, 'instantiatePlugins');
        $ref->setAccessible(true);
        $ref->invoke($loader, $this->tempDir);

        $this->assertEmpty($loader->getPluginInstances()->all());
    }

    public function testInstantiatePluginsFileDoesNotExist(): void
    {
        $this->classLoader->method('findFile')->willReturn('/non/existent/file.php');

        $loader = new class($this->classLoader) extends KernelPluginLoader {
            protected function loadPluginInfos(): void {
                $this->pluginInfos = [['name' => 'NoFile', 'baseClass' => 'stdClass']];
            }
        };

        $ref = new \ReflectionMethod(KernelPluginLoader::class, 'instantiatePlugins');
        $ref->setAccessible(true);
        $ref->invoke($loader, $this->tempDir);

        $this->assertEmpty($loader->getPluginInstances()->all());
    }

    public function testMapPsrPathsInvalidRoot(): void
    {
        $loader = new class($this->classLoader) extends KernelPluginLoader {
            public function callMapPsrPaths(string $plugin, array $psr, string $projectDir, string $pluginRootPath): array
            {
                $ref = new \ReflectionMethod(KernelPluginLoader::class, 'mapPsrPaths');
                $ref->setAccessible(true);
                return $ref->invoke($this, $plugin, $psr, $projectDir, $pluginRootPath);
            }
            protected function loadPluginInfos(): void {}
        };

        $this->expectException(\JBSNewMedia\VisBundle\Core\Exception\KernelPluginLoaderException::class);
        $this->expectExceptionMessage('needs to be a sub-directory');
        $loader->callMapPsrPaths('Plugin', ['src/'], '/project', '/outside');
    }

    public function testRegisterPluginNamespacesMissingAutoload(): void
    {
        $loader = new class($this->classLoader) extends KernelPluginLoader {
            public function setPluginInfos(array $infos): void { $this->pluginInfos = $infos; }
            protected function loadPluginInfos(): void {}
        };
        $loader->setPluginInfos([['name' => 'NoAutoload']]);

        $ref = new \ReflectionMethod(KernelPluginLoader::class, 'registerPluginNamespaces');
        $ref->setAccessible(true);

        $this->expectException(\JBSNewMedia\VisBundle\Core\Exception\KernelPluginLoaderException::class);
        $this->expectExceptionMessage('Required property `autoload` missing');
        $ref->invoke($loader, $this->tempDir);
    }

    public function testRegisterPluginNamespacesNoPsr(): void
    {
        $loader = new class($this->classLoader) extends KernelPluginLoader {
            public function setPluginInfos(array $infos): void { $this->pluginInfos = $infos; }
            protected function loadPluginInfos(): void {}
        };
        $loader->setPluginInfos([['name' => 'NoPsr', 'autoload' => []]]);

        $ref = new \ReflectionMethod(KernelPluginLoader::class, 'registerPluginNamespaces');
        $ref->setAccessible(true);

        $this->expectException(\JBSNewMedia\VisBundle\Core\Exception\KernelPluginLoaderException::class);
        $this->expectExceptionMessage('Required property `psr-4` or `psr-0` missing');
        $ref->invoke($loader, $this->tempDir);
    }
}
