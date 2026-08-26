<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisPluginCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class VisPluginCreateCommandTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_plugin_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir . '/config');

        // Create dummy files that the command expects to modify
        file_put_contents($this->tempDir . '/config/bundles.php', "<?php\nreturn [];\n");
        file_put_contents($this->tempDir . '/composer.json', json_encode(['autoload' => ['psr-4' => []]]));
        file_put_contents($this->tempDir . '/config/routes.yaml', "");
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testExecuteSuccess(): void
    {
        // Add existing autoload to test merging
        file_put_contents($this->tempDir . '/composer.json', json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'src/']]
        ]));

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);

        // Inputs: Name (Demo), Company (Acme), activate plugin (yes), update composer (yes), add routes (yes)
        $commandTester->setInputs(['Demo', 'Acme', 'yes', 'yes', 'yes']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Plugin Demo created successfully', $commandTester->getDisplay());

        // Check if plugin directory was created
        $pluginPath = $this->tempDir . '/plugins/acme/vis-demo-plugin';
        $this->assertDirectoryExists($pluginPath);
        $this->assertFileExists($pluginPath . '/src/VisDemoPluginBundle.php');

        // Check that the generated plugin composer.json is valid JSON
        $pluginComposer = json_decode((string) file_get_contents($pluginPath . '/composer.json'), true);
        $this->assertIsArray($pluginComposer);
        $this->assertSame(['Acme\\VisDemoPluginBundle\\' => 'src/'], $pluginComposer['autoload']['psr-4']);
        $this->assertSame('Acme\\VisDemoPluginBundle\\VisDemoPluginBundle', $pluginComposer['extra']['vis-plugin-class']);

        // Check the plugin is registered in plugins.json
        $plugins = json_decode((string) file_get_contents($this->tempDir . '/plugins/plugins.json'), true);
        $this->assertIsArray($plugins);
        $this->assertCount(1, $plugins);
        $this->assertSame('plugins/acme/vis-demo-plugin', $plugins[0]['path']);
        $this->assertSame('Acme\\VisDemoPluginBundle\\VisDemoPluginBundle', $plugins[0]['baseClass']);
        $this->assertTrue($plugins[0]['active']);

        // Check that the namespace was added to the root composer.json
        $composerContent = json_decode((string) file_get_contents($this->tempDir . '/composer.json'), true);
        $this->assertArrayHasKey('Acme\\VisDemoPluginBundle\\', $composerContent['autoload']['psr-4']);
        $this->assertArrayHasKey('App\\', $composerContent['autoload']['psr-4']);

        $routesContent = file_get_contents($this->tempDir . '/config/routes.yaml');
        $this->assertStringContainsString('vis_demo_plugin:', $routesContent);
    }

    public function testExecuteWithoutActivation(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);

        // Inputs: Name (Demo), Company (Acme), activate plugin (no), update composer (no), add routes (no)
        $commandTester->setInputs(['Demo', 'Acme', 'no', 'no', 'no']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Plugin Demo created successfully', $commandTester->getDisplay());
        $this->assertDirectoryExists($this->tempDir . '/plugins/acme/vis-demo-plugin');
        $this->assertFileDoesNotExist($this->tempDir . '/plugins/plugins.json');
    }

    public function testExecuteDirectoryExistsAndCancel(): void
    {
        $pluginPath = $this->tempDir . '/plugins/acme/vis-demo-plugin';
        $this->filesystem->mkdir($pluginPath);

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);

        // Inputs: Name, Company, confirm delete (no)
        $commandTester->setInputs(['Demo', 'Acme', 'no']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Creation cancelled', $commandTester->getDisplay());
    }

    public function testExecuteDirectoryExistsAndDelete(): void
    {
        $pluginPath = $this->tempDir . '/plugins/acme/vis-demo-plugin';
        $this->filesystem->mkdir($pluginPath);

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);

        // Inputs: Name, Company, confirm delete (yes), activate (no), update composer (no), add routes (no)
        $commandTester->setInputs(['Demo', 'Acme', 'yes', 'no', 'no', 'no']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Deleted existing directory', $commandTester->getDisplay());
        $this->assertDirectoryDoesNotExist($pluginPath);
    }
}
