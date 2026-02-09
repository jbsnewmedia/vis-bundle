<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core;

use Composer\Autoload\ClassLoader;
use JBSNewMedia\VisBundle\Core\Exception\KernelPluginLoaderException;
use JBSNewMedia\VisBundle\Core\KernelPluginLoader;
use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class KernelPluginLoaderExceptionTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private ClassLoader $classLoader;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_kernel_exception_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);
        $this->classLoader = $this->createMock(ClassLoader::class);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    private function getLoader(array $pluginInfos): KernelPluginLoader
    {
        return new class($this->classLoader, $pluginInfos) extends KernelPluginLoader {
            public function __construct($classLoader, private array $infos) { parent::__construct($classLoader); }
            protected function loadPluginInfos(): void { $this->pluginInfos = $this->infos; }
        };
    }

    public function testRegisterPluginNamespacesMissingAutoload(): void
    {
        $loader = $this->getLoader([
            ['name' => 'TestPlugin'] // Missing 'autoload'
        ]);

        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('Required property `autoload` missing');
        $loader->initializePlugins($this->tempDir);
    }

    public function testRegisterPluginNamespacesMissingPsr(): void
    {
        $loader = $this->getLoader([
            [
                'name' => 'TestPlugin',
                'autoload' => [] // Missing psr-4 or psr-0
            ]
        ]);

        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('Required property `psr-4` or `psr-0` missing');
        $loader->initializePlugins($this->tempDir);
    }

    public function testRegisterPluginNamespacesInvalidPath(): void
    {
        $loader = $this->getLoader([
            [
                'name' => 'TestPlugin',
                'path' => '/outside', // absolute path outside projectDir
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]
        ]);

        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('needs to be a sub-directory of the project dir');
        $loader->initializePlugins('/home/user/project');
    }

    public function testRegisterPluginNamespacesClassMapAuthoritative(): void
    {
        $pluginPath = 'plugins/TestPlugin';
        $this->filesystem->mkdir($this->tempDir . '/' . $pluginPath . '/src');

        $this->classLoader->method('isClassMapAuthoritative')->willReturn(true);
        $this->classLoader->expects($this->once())->method('addClassMap');

        $loader = $this->getLoader([
            [
                'name' => 'TestPlugin',
                'path' => $pluginPath,
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]
        ]);

        $loader->initializePlugins($this->tempDir);
    }

    public function testInstantiatePluginsSkipsOnMissingClass(): void
    {
        $loader = $this->getLoader([
            [
                'name' => 'TestPlugin',
                'baseClass' => 'NonExistent\\PluginClass',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]
        ]);

        $loader->initializePlugins($this->tempDir);
        $this->assertCount(0, $loader->getPluginInstances()->all());
    }
}
