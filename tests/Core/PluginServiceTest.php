<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core;

use JBSNewMedia\VisBundle\Core\PluginService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class PluginServiceTest extends TestCase
{
    private string $tempDir;
    private KernelInterface $kernel;
    private Filesystem $filesystem;
    private PluginService $service;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_plugin_service_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir . '/config');
        $this->filesystem->mkdir($this->tempDir . '/plugins');

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getProjectDir')->willReturn($this->tempDir);
        $this->kernel->method('getEnvironment')->willReturn('test');

        $this->service = new PluginService($this->kernel);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testLoadPluginsInfoFromJson(): void
    {
        $plugins = [
            ['name' => 'plugin1', 'active' => true],
            ['name' => 'plugin2', 'active' => false],
        ];
        file_put_contents($this->tempDir . '/plugins/plugins.json', json_encode($plugins));

        $this->assertCount(2, $this->service->loadPluginsInfoFromJson());
        $this->assertCount(1, $this->service->loadPluginsInfoFromJson('plugin1'));
    }

    public function testGetPluginData(): void
    {
        $plugins = [['name' => 'plugin1', 'active' => true]];
        file_put_contents($this->tempDir . '/plugins/plugins.json', json_encode($plugins));

        $this->assertEquals($plugins[0], $this->service->getPluginData('plugin1'));
        $this->assertNull($this->service->getPluginData('nonexistent'));
    }

    public function testLoadFromPluginPath(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/plugins/TestPlugin');
        file_put_contents($this->tempDir . '/plugins/TestPlugin/composer.json', json_encode([
            'name' => 'jbsnewmedia/test-plugin',
            'extra' => ['amicron-platform-plugin-class' => 'Test\\Plugin']
        ]));

        $plugins = $this->service->loadFromPluginPath();
        $this->assertCount(1, $plugins);
        $this->assertEquals('TestPlugin', $plugins[0]['name']);
    }

    public function testEnablePlugin(): void
    {
        // Setup a plugin in plugins folder
        $this->filesystem->mkdir($this->tempDir . '/plugins/TestPlugin/public');
        file_put_contents($this->tempDir . '/plugins/TestPlugin/public/test.txt', 'hello');
        file_put_contents($this->tempDir . '/plugins/TestPlugin/composer.json', json_encode([
            'name' => 'TestPlugin',
            'extra' => ['amicron-platform-plugin-class' => 'Test\\Plugin']
        ]));

        // Create dummy bin/console to avoid exec error
        $this->filesystem->mkdir($this->tempDir . '/bin');
        // We use a simple echo because 'exec' output is not captured by PluginService
        file_put_contents($this->tempDir . '/bin/console', "#!/usr/bin/env php\n<?php echo 'ok';");
        chmod($this->tempDir . '/bin/console', 0755);

        // Try to create a symlink in a more likely 'exec'-able location if /tmp is noexec
        // Actually, let's just use 'true' if the bin/console fails.
        // But we want to test that the command is CALLED.

        $this->service->enablePlugin('TestPlugin');

        $this->assertFileExists($this->tempDir . '/plugins/plugins.json');
        $data = json_decode(file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertTrue($data[0]['active']);

        // Check if public folder mirrored
        $this->assertFileExists($this->tempDir . '/public/bundles/TestPlugin/test.txt');
    }

    public function testDisablePlugin(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/plugins/TestPlugin');
        file_put_contents($this->tempDir . '/plugins/TestPlugin/composer.json', json_encode([
            'name' => 'TestPlugin',
            'extra' => ['amicron-platform-plugin-class' => 'Test\\Plugin']
        ]));

        $this->filesystem->mkdir($this->tempDir . '/public/bundles/TestPlugin');
        file_put_contents($this->tempDir . '/bin/console', "#!/usr/bin/env php\n<?php echo 'ok';");
        chmod($this->tempDir . '/bin/console', 0755);

        $this->service->disablePlugin('TestPlugin');

        $data = json_decode(file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertFalse($data[0]['active']);
        $this->assertDirectoryDoesNotExist($this->tempDir . '/public/bundles/TestPlugin');
    }

    public function testPluginUpdateLifecycle(): void
    {
        // Should not crash even if class doesn't exist
        $this->service->pluginUpdateLifecycle(['name' => 'TestPlugin', 'active' => true, 'baseClass' => 'NonExistent']);
        $this->assertTrue(true);
    }

    public function testPluginActivationLifecycle(): void
    {
        $this->service->pluginActivationLifecycle(['name' => 'TestPlugin', 'active' => true, 'baseClass' => 'NonExistent']);
        $this->assertTrue(true);
    }

    public function testEnablePluginNotFound(): void
    {
        $this->assertFalse($this->service->enablePlugin('NonExistent'));
    }

    public function testDisablePluginNotFound(): void
    {
        $this->assertFalse($this->service->disablePlugin('NonExistent'));
    }

    public function testRunPluginLifecycleNoBaseClass(): void
    {
        $ref = new \ReflectionMethod(PluginService::class, 'runPluginLifecycle');
        $ref->setAccessible(true);
        $ref->invoke($this->service, ['active' => true]);
        $this->assertTrue(true);
    }

    public function testRunPluginLifecycleInactive(): void
    {
        $ref = new \ReflectionMethod(PluginService::class, 'runPluginLifecycle');
        $ref->setAccessible(true);
        $ref->invoke($this->service, ['active' => false, 'baseClass' => 'stdClass']);
        $this->assertTrue(true);
    }

    public function testCopyPluginsPublicFolderNoPublic(): void
    {
        $this->service->copyPluginsPublicFolder(['name' => 'Test']);
        $this->assertTrue(true);
    }

    public function testStorePluginsNew(): void
    {
        $pluginData = ['name' => 'NewPlugin', 'active' => true];
        $ref = new \ReflectionMethod(PluginService::class, 'storePlugins');
        $ref->setAccessible(true);
        $ref->invoke($this->service, $pluginData);

        $this->assertFileExists($this->tempDir . '/plugins/plugins.json');
        $data = json_decode(file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertEquals('NewPlugin', $data[0]['name']);
    }

    public function testLoadFromPluginPathGlobFailure(): void
    {
        // To test glob failure we would need to mock glob, which is hard.
        // But we can test when no directories are found.
        $emptyDir = sys_get_temp_dir() . '/empty_plugins_' . uniqid();
        $this->filesystem->mkdir($emptyDir . '/plugins');
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($emptyDir);
        $service = new PluginService($kernel);

        $this->assertEmpty($service->loadFromPluginPath());
        $this->filesystem->remove($emptyDir);
    }

    public function testLoadPluginsInfoFromJsonInvalidJson(): void
    {
        file_put_contents($this->tempDir . '/plugins/plugins.json', '{invalid');
        $this->expectException(\JsonException::class);
        $this->service->loadPluginsInfoFromJson();
    }

    public function testLoadPluginsInfoFromJsonNotArray(): void
    {
        file_put_contents($this->tempDir . '/plugins/plugins.json', '"string"');
        $this->assertEmpty($this->service->loadPluginsInfoFromJson());
    }

    public function testPluginActivationLifecycleInactive(): void
    {
        // Setup a dummy class that exists
        $this->service->pluginActivationLifecycle(['baseClass' => 'stdClass', 'active' => false]);
        $this->assertTrue(true);
    }
}
