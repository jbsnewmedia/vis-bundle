<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core;

use JBSNewMedia\VisBundle\Core\PluginService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class PluginServiceEnablePluginTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private PluginService $service;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/plugin_service_enable_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir . '/plugins/TestPlugin');
        $this->filesystem->mkdir($this->tempDir . '/public');

        file_put_contents($this->tempDir . '/plugins/TestPlugin/composer.json', json_encode([
            'extra' => ['amicron-platform-plugin-class' => 'stdClass']
        ]));

        // seed plugins.json as empty list
        $this->filesystem->mkdir($this->tempDir . '/plugins');
        file_put_contents($this->tempDir . '/plugins/plugins.json', json_encode([]));

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->tempDir);

        $this->service = new PluginService($kernel, $this->tempDir, 'test');

        // create console to make enable/disable try to exec
        $this->filesystem->mkdir($this->tempDir . '/bin');
        file_put_contents($this->tempDir . '/bin/console', "#!/usr/bin/env php\n<?php exit(0);");
        chmod($this->tempDir . '/bin/console', 0755);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testEnablePluginCreatesOrUpdatesPluginsJson(): void
    {
        // Internally `enablePlugin` calls createPluginData/load/store pathes
        $result = $this->service->enablePlugin('TestPlugin');
        $this->assertTrue($result);

        $plugins = json_decode((string) file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertIsArray($plugins);
        $names = array_map(static fn($p) => is_array($p) ? ($p['name'] ?? null) : null, $plugins);
        $this->assertContains('TestPlugin', $names);
    }
}
