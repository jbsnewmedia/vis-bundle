<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core;

use Composer\Autoload\ClassLoader;
use JBSNewMedia\VisBundle\Core\Exception\KernelPluginLoaderException;
use JBSNewMedia\VisBundle\Core\KernelPluginLoader;
use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class KernelPluginLoaderTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private ClassLoader $classLoader;
    private KernelPluginLoader $loader;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_kernel_loader_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);

        $this->classLoader = $this->createMock(ClassLoader::class);
        $this->loader = new class($this->classLoader) extends KernelPluginLoader {
            public function setPluginInfos(array $infos): void { $this->pluginInfos = $infos; }
            public function callRegisterPluginNamespaces(string $dir): void {
                $ref = new \ReflectionMethod(KernelPluginLoader::class, 'registerPluginNamespaces');
                $ref->setAccessible(true);
                $ref->invoke($this, $dir);
            }
            public function callInstantiatePlugins(string $dir): void {
                $ref = new \ReflectionMethod(KernelPluginLoader::class, 'instantiatePlugins');
                $ref->setAccessible(true);
                $ref->invoke($this, $dir);
            }
            protected function loadPluginInfos(): void {}
        };
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testGetPluginDir(): void
    {
        $this->assertEquals($this->tempDir . '/plugins', $this->loader->getPluginDir($this->tempDir));
    }

    public function testGetBundles(): void
    {
        // Not initialized should yield nothing
        $this->assertEmpty(iterator_to_array($this->loader->getBundles([], [])));

        $this->loader->initializePlugins($this->tempDir);

        $plugin = new class(true) extends AbstractVisBundle {
        };
        $this->loader->getPluginInstances()->add($plugin);

        $bundles = iterator_to_array($this->loader->getBundles([], []));
        $this->assertContains($plugin, $bundles);
        $this->assertContains($this->loader, $bundles);

        // Already loaded should skip
        $bundles2 = iterator_to_array($this->loader->getBundles([], [$plugin->getName(), $this->loader->getName()]));
        $this->assertNotContains($plugin, $bundles2);
        $this->assertNotContains($this->loader, $bundles2);
    }

    public function testInitializePluginsTwice(): void
    {
        $this->loader->initializePlugins($this->tempDir);
        $this->loader->initializePlugins($this->tempDir);
        $this->assertTrue(true); // Should not crash
    }

    public function testBuildNotInitialized(): void
    {
        $container = new ContainerBuilder();
        $this->loader->build($container);
        $this->assertFalse($container->has($this->loader::class));
    }

    public function testBuildExistingDefinition(): void
    {
        $this->loader->initializePlugins($this->tempDir);
        $container = new ContainerBuilder();

        $plugin = $this->createMock(AbstractVisBundle::class);
        $plugin->method('isActive')->willReturn(true);
        $pluginClass = $plugin::class;

        $container->register($pluginClass, $pluginClass);

        $this->loader->getPluginInstances()->add($plugin);
        $this->loader->build($container);
        $this->assertTrue($container->has($pluginClass));
    }

    public function testRegisterPluginNamespacesPsr0WithClassMapAuthoritative(): void
    {
        $this->loader->setPluginInfos([
            [
                'name' => 'Psr0Plugin',
                'path' => 'plugins/Psr0Plugin',
                'autoload' => ['psr-0' => ['Psr0\\' => 'lib/']]
            ]
        ]);
        $this->filesystem->mkdir($this->tempDir . '/plugins/Psr0Plugin/lib');
        file_put_contents($this->tempDir . '/plugins/Psr0Plugin/lib/File.php', "<?php namespace Psr0; class File {}");

        $this->classLoader->method('isClassMapAuthoritative')->willReturn(true);
        $this->classLoader->expects($this->once())->method('addClassMap');

        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testInstantiatePluginsFileNotFound(): void
    {
        $this->classLoader->method('findFile')->willReturn($this->tempDir . '/non_existent.php');

        $this->loader->setPluginInfos([
            [
                'name' => 'TestPlugin',
                'baseClass' => InstantiateDummyPlugin::class,
                'active' => true,
                'path' => 'plugins/TestPlugin',
                'autoload' => ['psr-4' => ['' => '']]
            ]
        ]);

        $this->loader->callInstantiatePlugins($this->tempDir);
        $this->assertEmpty($this->loader->getPluginInstances()->all());
    }

    public function testBuild(): void
    {
        $this->loader->initializePlugins($this->tempDir);
        $container = new ContainerBuilder();

        $plugin = $this->createMock(AbstractVisBundle::class);
        $plugin->method('isActive')->willReturn(true);
        $pluginClass = $plugin::class;

        $this->loader->getPluginInstances()->add($plugin);
        $this->loader->build($container);
        $this->assertTrue($container->has($pluginClass));
    }

    public function testRegisterPluginNamespaces(): void
    {
        $this->loader->setPluginInfos([
            [
                'name' => 'TestPlugin',
                'path' => 'plugins/TestPlugin',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]
        ]);

        $this->classLoader->expects($this->once())
            ->method('addPsr4')
            ->with('Test\\', [$this->tempDir . '/plugins/TestPlugin/src/']);

        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testGetPluginInstance(): void
    {
        $plugin = new class(true) extends AbstractVisBundle {};
        $this->loader->getPluginInstances()->add($plugin);
        $pluginClass = $plugin::class;

        $this->assertSame($plugin, $this->loader->getPluginInstance($pluginClass));

        $inactivePlugin = new class(false) extends AbstractVisBundle {};
        $this->loader->getPluginInstances()->add($inactivePlugin);
        $this->assertNull($this->loader->getPluginInstance($inactivePlugin::class));

        $this->assertNull($this->loader->getPluginInstance('NonExistent'));
    }

    public function testGetClassLoader(): void
    {
        $this->assertSame($this->classLoader, $this->loader->getClassLoader());
    }

    public function testGetPluginInfos(): void
    {
        $infos = [['name' => 'test']];
        $this->loader->setPluginInfos($infos);
        $this->assertEquals($infos, $this->loader->getPluginInfos());
    }

    public function testRegisterPluginNamespacesComplex(): void
    {
        $this->loader->setPluginInfos([
            [
                'name' => 'TestPlugin',
                'path' => 'plugins/TestPlugin',
                'autoload' => [
                    'psr-4' => ['Test\\' => 'src/'],
                    'psr-0' => ['Old\\' => 'lib/'],
                    'classmap' => ['Extra/']
                ]
            ]
        ]);

        // Expect calls for PSR-4, PSR-0, and Classmap
        $this->classLoader->expects($this->once())->method('addPsr4');
        $this->classLoader->expects($this->once())->method('add');
        // addClassMap is called if classMapAuthoritative is true, which it is not by default in this mock
        // but wait, the code also checks for classmap in registerPluginNamespaces?
        // No, it doesn't. registerPluginNamespaces only handles psr-4 and psr-0.
        // The classmap in autoload is handled by Composer usually, but our KernelPluginLoader
        // seems to ignore it unless classMapAuthoritative is true and it's PSR-4/0.

        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testInstantiatePluginsActive(): void
    {
        $this->classLoader->method('findFile')->willReturn(__FILE__);

        $this->loader->setPluginInfos([
            [
                'name' => 'TestPlugin',
                'baseClass' => InstantiateDummyPlugin::class,
                'active' => true,
                'path' => 'plugins/TestPlugin',
                'autoload' => ['psr-4' => ['JBSNewMedia\\VisBundle\\Tests\\Core\\' => '']]
            ]
        ]);

        $this->loader->callInstantiatePlugins($this->tempDir);
        $instances = $this->loader->getPluginInstances()->all();
        $this->assertCount(1, $instances);
        $this->assertInstanceOf(InstantiateDummyPlugin::class, reset($instances));
    }

    public function testInstantiatePluginsInactive(): void
    {
        $this->classLoader->method('findFile')->willReturn(__FILE__);

        $this->loader->setPluginInfos([
            [
                'name' => 'TestPlugin',
                'baseClass' => InstantiateDummyPlugin::class,
                'active' => false,
                'path' => 'plugins/TestPlugin',
                'autoload' => ['psr-4' => ['JBSNewMedia\\VisBundle\\Tests\\Core\\' => '']]
            ]
        ]);

        $this->loader->callInstantiatePlugins($this->tempDir);
        $instances = $this->loader->getPluginInstances()->all();
        $this->assertCount(1, $instances);
        $plugin = reset($instances);
        $this->assertFalse($plugin->isActive());
    }

    public function testInstantiatePluginsThrowsExceptionOnWrongBaseClass(): void
    {
        $this->classLoader->method('findFile')->willReturn(__FILE__);

        $this->loader->setPluginInfos([
            [
                'name' => 'WrongPlugin',
                'baseClass' => \stdClass::class,
                'active' => true,
                'path' => 'plugins/WrongPlugin',
                'autoload' => ['psr-4' => ['' => '']]
            ]
        ]);

        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('must extend');
        $this->loader->callInstantiatePlugins($this->tempDir);
    }

    public function testRegisterPluginNamespacesInvalidAutoload(): void
    {
        $this->loader->setPluginInfos([
            ['name' => 'Invalid', 'autoload' => 'string']
        ]);
        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('Required property `autoload` missing');
        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testRegisterPluginNamespacesNoPsr(): void
    {
        $this->loader->setPluginInfos([
            ['name' => 'Invalid', 'autoload' => []]
        ]);
        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('Required property `psr-4` or `psr-0` missing');
        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testMapPsrPathsInvalidRoot(): void
    {
        $this->loader->setPluginInfos([
            [
                'name' => 'Invalid',
                'path' => '/outside/project',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]
        ]);
        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('needs to be a sub-directory of the project dir');
        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testGetAbsolutePluginRootPathRelative(): void
    {
        $ref = new \ReflectionMethod(KernelPluginLoader::class, 'getAbsolutePluginRootPath');
        $ref->setAccessible(true);
        $result = $ref->invoke($this->loader, '/project', 'plugins/test');
        $this->assertEquals('/project/plugins/test', $result);
    }

    public function testGetPluginDirAbsolute(): void
    {
        $loader = new class($this->classLoader) extends KernelPluginLoader {
            protected function loadPluginInfos(): void {}
            public function setPluginDir(string $dir): void {
                $ref = new \ReflectionProperty(KernelPluginLoader::class, 'pluginDir');
                $ref->setAccessible(true);
                $ref->setValue($this, $dir);
            }
        };

        $loader->setPluginDir('/absolute/path');
        $this->assertEquals('/absolute/path', $loader->getPluginDir($this->tempDir));
    }

    public function testInitializePluginsWithEmptyInfos(): void
    {
        $loader = new class($this->classLoader) extends KernelPluginLoader {
            protected function loadPluginInfos(): void {
                $this->pluginInfos = [];
            }
        };
        $loader->initializePlugins($this->tempDir);
        $this->assertEmpty($loader->getPluginInfos());
    }

    public function testRegisterPluginNamespacesMissingAutoloadThrowsException(): void
    {
        $this->loader->setPluginInfos([
            ['name' => 'BadPlugin']
        ]);
        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('Required property `autoload` missing');
        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testRegisterPluginNamespacesEmptyAutoloadThrowsException(): void
    {
        $this->loader->setPluginInfos([
            ['name' => 'BadPlugin', 'autoload' => []]
        ]);
        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('Required property `psr-4` or `psr-0` missing');
        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testRegisterPluginNamespacesInvalidPathThrowsException(): void
    {
        $this->loader->setPluginInfos([
            [
                'name' => 'OutsidePlugin',
                'path' => '/tmp/outside',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]
        ]);
        $this->expectException(KernelPluginLoaderException::class);
        $this->expectExceptionMessage('needs to be a sub-directory');
        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testRegisterPluginNamespacesWithClassMapAuthoritative(): void
    {
        $this->loader->setPluginInfos([
            [
                'name' => 'MapPlugin',
                'path' => 'plugins/MapPlugin',
                'autoload' => ['psr-4' => ['Map\\' => 'src/']]
            ]
        ]);
        $this->filesystem->mkdir($this->tempDir . '/plugins/MapPlugin/src');
        file_put_contents($this->tempDir . '/plugins/MapPlugin/src/File.php', "<?php namespace Map; class File {}");

        $this->classLoader->method('isClassMapAuthoritative')->willReturn(true);
        $this->classLoader->expects($this->once())->method('addClassMap');

        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testRegisterPluginNamespacesWithBaseClassAsName(): void
    {
        $this->loader->setPluginInfos([
            [
                'baseClass' => 'BaseClass',
                'path' => 'plugins/TestPlugin',
                'autoload' => ['psr-4' => ['Test\\' => 'src/']]
            ]
        ]);

        $this->classLoader->expects($this->once())->method('addPsr4');
        $this->loader->callRegisterPluginNamespaces($this->tempDir);
    }

    public function testRegisterPluginNamespacesInvalidInfo(): void
    {
        $this->loader->setPluginInfos([
            'not_an_array'
        ]);

        $this->loader->callRegisterPluginNamespaces($this->tempDir);
        $this->assertTrue(true); // Should just continue
    }

    public function testInstantiatePluginsInvalidInfo(): void
    {
        $this->loader->setPluginInfos([
            'not_an_array'
        ]);

        $this->loader->callInstantiatePlugins($this->tempDir);
        $this->assertTrue(true); // Should just continue
    }

    public function testInstantiatePluginsBaseClassNotString(): void
    {
        $this->loader->setPluginInfos([
            ['baseClass' => 123]
        ]);

        $this->loader->callInstantiatePlugins($this->tempDir);
        $this->assertTrue(true); // Should just continue
    }
}

class InstantiateDummyPlugin extends AbstractVisBundle {}
