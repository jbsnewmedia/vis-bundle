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

        // Inputs: Name (Demo), Company (Acme), add bundle (yes), update composer (yes), add routes (yes)
        $commandTester->setInputs(['Demo', 'Acme', 'yes', 'yes', 'yes']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Plugin Demo created successfully', $commandTester->getDisplay());

        // Check if plugin directory was created
        $pluginPath = $this->tempDir . '/plugins/acme/vis-demo-plugin';
        $this->assertDirectoryExists($pluginPath);
        $this->assertFileExists($pluginPath . '/src/VisDemoPluginBundle.php');

        // Check if files were updated
        $bundlesContent = file_get_contents($this->tempDir . '/config/bundles.php');
        $this->assertStringContainsString('Acme\\VisDemoPluginBundle\\VisDemoPluginBundle::class => [\'all\' => true]', $bundlesContent);

        $composerContent = json_decode(file_get_contents($this->tempDir . '/composer.json'), true);
        $this->assertArrayHasKey('Acme\\VisDemoPluginBundle\\', $composerContent['autoload']['psr-4']);
        $this->assertArrayHasKey('App\\', $composerContent['autoload']['psr-4']);

        $routesContent = file_get_contents($this->tempDir . '/config/routes.yaml');
        $this->assertStringContainsString('vis_demo_plugin:', $routesContent);
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

        // Inputs: Name, Company, confirm delete (yes), add bundle (no), update composer (no), add routes (no)
        $commandTester->setInputs(['Demo', 'Acme', 'yes', 'no', 'no', 'no']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Deleted existing directory', $commandTester->getDisplay());
        $this->assertDirectoryDoesNotExist($pluginPath);
    }
}
