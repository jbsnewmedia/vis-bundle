<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisPluginDisableCommand;
use JBSNewMedia\VisBundle\Command\VisPluginEnableCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class VisPluginEnableDisableCommandTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_plugin_toggle_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir . '/plugins/TestPlugin');
        $this->filesystem->mkdir($this->tempDir . '/public');

        file_put_contents($this->tempDir . '/plugins/TestPlugin/composer.json', (string) json_encode([
            'autoload' => ['psr-4' => ['Acme\\VisTestPluginBundle\\' => 'src/']],
            'extra' => ['amicron-platform-plugin-class' => 'Acme\\VisTestPluginBundle\\VisTestPluginBundle'],
        ]));
        file_put_contents($this->tempDir . '/plugins/plugins.json', (string) json_encode([]));

        $this->kernel = $this->createStub(KernelInterface::class);
        $this->kernel->method('getProjectDir')->willReturn($this->tempDir);
        $this->kernel->method('getEnvironment')->willReturn('test');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testEnablePluginSuccess(): void
    {
        $commandTester = new CommandTester(new VisPluginEnableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'TestPlugin']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('enabled successfully', $commandTester->getDisplay());

        $plugins = json_decode((string) file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertIsArray($plugins);
        $this->assertCount(1, $plugins);
        $this->assertSame('TestPlugin', $plugins[0]['name']);
        $this->assertTrue($plugins[0]['active']);
        $this->assertSame('Acme\\VisTestPluginBundle\\VisTestPluginBundle', $plugins[0]['baseClass']);
    }

    public function testEnablePluginFailsOnMissingDirectory(): void
    {
        $commandTester = new CommandTester(new VisPluginEnableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'UnknownPlugin']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Plugin directory not found', $commandTester->getDisplay());
    }

    public function testEnablePluginNestedCompanyLayout(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/plugins/acme/vis-nested-plugin');
        file_put_contents($this->tempDir . '/plugins/acme/vis-nested-plugin/composer.json', (string) json_encode([
            'autoload' => ['psr-4' => ['Acme\\VisNestedPluginBundle\\' => 'src/']],
            'extra' => ['amicron-platform-plugin-class' => 'Acme\\VisNestedPluginBundle\\VisNestedPluginBundle'],
        ]));

        $commandTester = new CommandTester(new VisPluginEnableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'vis-nested-plugin']);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $plugins = json_decode((string) file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertIsArray($plugins);
        $this->assertCount(1, $plugins);
        $this->assertSame('plugins/acme/vis-nested-plugin', $plugins[0]['path']);
        $this->assertSame('acme/vis-nested-plugin', $plugins[0]['name']);
        $this->assertTrue($plugins[0]['active']);
    }

    public function testEnablePluginUpdatesExistingEntryWithoutName(): void
    {
        file_put_contents($this->tempDir . '/plugins/plugins.json', (string) json_encode([
            [
                'path' => 'plugins/TestPlugin',
                'baseClass' => 'Acme\\VisTestPluginBundle\\VisTestPluginBundle',
                'autoload' => ['psr-4' => ['Acme\\VisTestPluginBundle\\' => 'src/']],
                'active' => false,
                'public' => false,
            ],
        ]));

        $commandTester = new CommandTester(new VisPluginEnableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'TestPlugin']);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $plugins = json_decode((string) file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertIsArray($plugins);
        $this->assertCount(1, $plugins);
        $this->assertTrue($plugins[0]['active']);
    }

    public function testEnablePluginFailsOnMissingPluginClass(): void
    {
        file_put_contents($this->tempDir . '/plugins/TestPlugin/composer.json', (string) json_encode([
            'autoload' => ['psr-4' => ['Acme\\VisTestPluginBundle\\' => 'src/']],
        ]));

        $commandTester = new CommandTester(new VisPluginEnableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'TestPlugin']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('cannot be enabled', $commandTester->getDisplay());
    }

    public function testDisablePluginSuccess(): void
    {
        file_put_contents($this->tempDir . '/plugins/plugins.json', (string) json_encode([
            [
                'name' => 'TestPlugin',
                'path' => 'plugins/TestPlugin',
                'baseClass' => 'Acme\\VisTestPluginBundle\\VisTestPluginBundle',
                'autoload' => ['psr-4' => ['Acme\\VisTestPluginBundle\\' => 'src/']],
                'active' => true,
                'public' => false,
            ],
        ]));

        $commandTester = new CommandTester(new VisPluginDisableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'TestPlugin']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('disabled successfully', $commandTester->getDisplay());

        $plugins = json_decode((string) file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertIsArray($plugins);
        $this->assertCount(1, $plugins);
        $this->assertFalse($plugins[0]['active']);
    }

    public function testDisablePluginFailsOnMissingDirectory(): void
    {
        $commandTester = new CommandTester(new VisPluginDisableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'UnknownPlugin']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Plugin directory not found', $commandTester->getDisplay());
    }

    public function testDisablePluginFailsOnMissingPluginClass(): void
    {
        file_put_contents($this->tempDir . '/plugins/TestPlugin/composer.json', (string) json_encode([
            'autoload' => ['psr-4' => ['Acme\\VisTestPluginBundle\\' => 'src/']],
        ]));

        $commandTester = new CommandTester(new VisPluginDisableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'TestPlugin']);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('cannot be disabled', $commandTester->getDisplay());
    }

    public function testDisablePluginNestedCompanyLayout(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/plugins/acme/vis-nested-plugin');
        file_put_contents($this->tempDir . '/plugins/acme/vis-nested-plugin/composer.json', (string) json_encode([
            'autoload' => ['psr-4' => ['Acme\\VisNestedPluginBundle\\' => 'src/']],
            'extra' => ['amicron-platform-plugin-class' => 'Acme\\VisNestedPluginBundle\\VisNestedPluginBundle'],
        ]));

        $commandTester = new CommandTester(new VisPluginDisableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => 'vis-nested-plugin']);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $plugins = json_decode((string) file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertIsArray($plugins);
        $this->assertCount(1, $plugins);
        $this->assertFalse($plugins[0]['active']);
    }

    public function testEnablePluginFailsOnNonStringName(): void
    {
        $commandTester = new CommandTester(new VisPluginEnableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => ['array-value']]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Plugin name must be a string', $commandTester->getDisplay());
    }

    public function testDisablePluginFailsOnNonStringName(): void
    {
        $commandTester = new CommandTester(new VisPluginDisableCommand($this->kernel));
        $exitCode = $commandTester->execute(['name' => ['array-value']]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Plugin name must be a string', $commandTester->getDisplay());
    }
}
