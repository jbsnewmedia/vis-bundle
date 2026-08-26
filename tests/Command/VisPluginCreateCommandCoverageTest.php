<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisPluginCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class VisPluginCreateCommandCoverageTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_plugin_create_coverage_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    private function invokePrivate(object $object, string $methodName, array $args = [])
    {
        $ref = new \ReflectionMethod($object, $methodName);
        $ref->setAccessible(true);
        return $ref->invokeArgs($object, $args);
    }

    public function testActivatePluginInPluginsJsonNoFile(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        // plugins.json doesn't exist and gets created with the new entry
        $this->invokePrivate($command, 'activatePluginInPluginsJson', ['Test', 'Company', 'plugins/company/vis-test-plugin']);

        $plugins = json_decode((string) file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertIsArray($plugins);
        $this->assertCount(1, $plugins);
        $this->assertSame('Company\\VisTestPluginBundle\\VisTestPluginBundle', $plugins[0]['baseClass']);
    }

    public function testUpdateRootComposerNoFile(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        // composer.json doesn't exist
        $this->invokePrivate($command, 'updateRootComposer', ['Test', 'plugins/test', 'Company']);
        $this->assertFileDoesNotExist($this->tempDir . '/composer.json');
    }

    public function testAddRoutesToConfigNoFile(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        // routes.yaml doesn't exist
        $this->invokePrivate($command, 'addRoutesToConfig', ['Test', 'plugins/test']);
        $this->assertFileDoesNotExist($this->tempDir . '/config/routes.yaml');
    }

    public function testActivatePluginInPluginsJsonInvalidJson(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/plugins');
        $file = $this->tempDir . '/plugins/plugins.json';
        file_put_contents($file, "{invalid");

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $this->invokePrivate($command, 'activatePluginInPluginsJson', ['Test', 'Company', 'plugins/company/vis-test-plugin']);

        $this->assertEquals("{invalid", file_get_contents($file));
    }

    public function testUpdateRootComposerInvalidJson(): void
    {
        $file = $this->tempDir . '/composer.json';
        file_put_contents($file, "{invalid");

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $this->invokePrivate($command, 'updateRootComposer', ['Test', 'plugins/test', 'Company']);

        $this->assertEquals("{invalid", file_get_contents($file));
    }

    public function testAddRoutesToConfigAlreadyExists(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/config');
        $file = $this->tempDir . '/config/routes.yaml';
        file_put_contents($file, "vis_test_plugin: {}");

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $this->invokePrivate($command, 'addRoutesToConfig', ['Test', 'plugins/test']);

        $this->assertEquals("vis_test_plugin: {}", file_get_contents($file));
    }
}
