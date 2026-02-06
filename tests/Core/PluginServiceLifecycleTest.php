<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core;

use JBSNewMedia\VisBundle\Core\PluginInstallContext;
use JBSNewMedia\VisBundle\Core\PluginService;
use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

final class PluginServiceLifecycleTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/vis_plugin_service_lifecycle_'.uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);

        // Prepare minimal structure used by PluginService
        $this->filesystem->mkdir($this->tempDir.'/plugins/Demo');
        $this->filesystem->mkdir($this->tempDir.'/public');

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getProjectDir')->willReturn($this->tempDir);
        $this->kernel->method('getEnvironment')->willReturn('test');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testPluginActivationLifecycleInvokesActivateOnExistingClass(): void
    {
        $service = new PluginService($this->kernel);

        // Ensure the plugin base path exists so plugin can write into it
        $this->filesystem->mkdir($this->tempDir.'/plugins/Demo');

        $class = \JBSNewMedia\VisBundle\Tests\Core\Fixtures\LifecycleDummyPlugin::class;

        $pluginData = [
            'name' => 'Demo',
            'path' => 'plugins/Demo',
            'baseClass' => $class,
            'active' => true,
        ];

        // Pre-condition: marker file must not exist
        $markerFile = $this->tempDir.'/plugins/Demo/activated.txt';
        $this->assertFileDoesNotExist($markerFile);

        // When
        $service->pluginActivationLifecycle($pluginData);

        // Then
        $this->assertFileExists($markerFile);
        $this->assertSame('activated', trim((string) file_get_contents($markerFile)));
    }

    public function testCreatePluginDataReturnsNullIfComposerMissing(): void
    {
        $service = new PluginService($this->kernel);
        // No composer.json exists in the directory => createPluginData would return null via the private loader path
        // We indirectly hit it by calling enablePlugin with non-existing plugin and assert it returns false
        $this->assertFalse($service->enablePlugin('NonExistingPlugin'));
    }
}

namespace JBSNewMedia\VisBundle\Tests\Core\Fixtures;

use JBSNewMedia\VisBundle\Core\PluginInstallContext;
use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;

/**
 * Minimal concrete plugin used to test PluginService lifecycle.
 */
final class LifecycleDummyPlugin extends AbstractVisBundle
{
    public function activate(?PluginInstallContext $context = null): void
    {
        // Create a small marker file inside the plugin base path to verify invocation
        @file_put_contents($this->getBasePath().'/activated.txt', 'activated');
    }
}
