<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisPluginCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class VisPluginCreateCommandPrivateTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private VisPluginCreateCommand $command;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_plugin_private_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir . '/config');

        file_put_contents($this->tempDir . '/config/bundles.php', "<?php\nreturn [];\n");
        file_put_contents($this->tempDir . '/composer.json', json_encode(['autoload' => ['psr-4' => []]]));
        file_put_contents($this->tempDir . '/config/routes.yaml', "");

        $this->command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    private function callMethod(string $name, array $args): mixed
    {
        $class = new \ReflectionClass(VisPluginCreateCommand::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->command, $args);
    }

    public function testCreatePluginStructure(): void
    {
        $path = $this->tempDir . '/plugins/acme/vis-test-plugin';
        $this->callMethod('createPluginStructure', [$path, 'Test', 'Acme']);

        $this->assertDirectoryExists($path . '/src/Controller');
        $this->assertDirectoryExists($path . '/src/Plugin');
        $this->assertDirectoryExists($path . '/src/DependencyInjection');
        $this->assertFileExists($path . '/src/VisTestPluginBundle.php');
        $this->assertFileExists($path . '/composer.json');
    }

    public function testAddBundleToConfig(): void
    {
        $this->callMethod('addBundleToConfig', ['Test', 'Acme']);
        $content = file_get_contents($this->tempDir . '/config/bundles.php');
        $this->assertStringContainsString('Acme\\VisTestPluginBundle\\VisTestPluginBundle::class => [\'all\' => true]', $content);
    }

    public function testUpdateRootComposer(): void
    {
        $this->callMethod('updateRootComposer', ['Test', 'acme/vis-test-plugin', 'Acme']);
        $composer = json_decode(file_get_contents($this->tempDir . '/composer.json'), true);
        $this->assertArrayHasKey('Acme\\VisTestPluginBundle\\', $composer['autoload']['psr-4']);
        $this->assertEquals('acme/vis-test-plugin/src/', $composer['autoload']['psr-4']['Acme\\VisTestPluginBundle\\']);
    }

    public function testAddBundleToConfigAlreadyExists(): void
    {
        $bundleClass = 'Acme\\VisTestPluginBundle\\VisTestPluginBundle';
        file_put_contents($this->tempDir . '/config/bundles.php', "<?php\nreturn [\n    $bundleClass::class => ['all' => true],\n];\n");
        $this->callMethod('addBundleToConfig', ['Test', 'Acme']);
        $content = file_get_contents($this->tempDir . '/config/bundles.php');
        // Should not be changed if already exists
        $this->assertEquals("<?php\nreturn [\n    $bundleClass::class => ['all' => true],\n];\n", $content);
    }

    public function testAddRoutesToConfigAlreadyExists(): void
    {
        file_put_contents($this->tempDir . '/config/routes.yaml', "vis_test_plugin:\n    resource: .\n");
        $this->callMethod('addRoutesToConfig', ['Test', 'acme/vis-test-plugin']);
        $content = file_get_contents($this->tempDir . '/config/routes.yaml');
        $this->assertEquals("vis_test_plugin:\n    resource: .\n", $content);
    }

    public function testUpdateRootComposerInvalidJson(): void
    {
        file_put_contents($this->tempDir . '/composer.json', "{invalid");
        $this->callMethod('updateRootComposer', ['Test', 'acme/vis-test-plugin', 'Acme']);
        $content = file_get_contents($this->tempDir . '/composer.json');
        $this->assertEquals("{invalid", $content);
    }

    public function testAddBundleToConfigNoFile(): void
    {
        unlink($this->tempDir . '/config/bundles.php');
        $this->callMethod('addBundleToConfig', ['Test', 'Acme']);
        $this->assertFileDoesNotExist($this->tempDir . '/config/bundles.php');
    }

    public function testUpdateRootComposerNoFile(): void
    {
        unlink($this->tempDir . '/composer.json');
        $this->callMethod('updateRootComposer', ['Test', 'acme/vis-test-plugin', 'Acme']);
        $this->assertFileDoesNotExist($this->tempDir . '/composer.json');
    }

    public function testAddRoutesToConfigNoFile(): void
    {
        unlink($this->tempDir . '/config/routes.yaml');
        $this->callMethod('addRoutesToConfig', ['Test', 'acme/vis-test-plugin']);
        $this->assertFileDoesNotExist($this->tempDir . '/config/routes.yaml');
    }

    public function testUpdateRootComposerNoAutoload(): void
    {
        file_put_contents($this->tempDir . '/composer.json', json_encode(['name' => 'test']));
        $this->callMethod('updateRootComposer', ['Test', 'acme/vis-test-plugin', 'Acme']);
        $composer = json_decode(file_get_contents($this->tempDir . '/composer.json'), true);
        $this->assertArrayHasKey('autoload', $composer);
        $this->assertArrayHasKey('psr-4', $composer['autoload']);
    }

    public function testExecuteValidatesInput(): void
    {
        $tester = new \Symfony\Component\Console\Tester\CommandTester($this->command);

        // Inputs logic:
        // 1. Ask Name -> empty -> error
        // 2. Ask Name -> invalid -> error
        // 3. Ask Name -> valid 'Demo'
        // 4. Ask Company -> empty -> error
        // 5. Ask Company -> invalid -> error
        // 6. Ask Company -> valid 'Acme'
        // 7-10. Confirms -> all 'no'
        $tester->setInputs(['', 'Inv@lid', 'Demo', '', 'Inv@lid', 'Acme', 'no', 'no', 'no', 'no']);
        $tester->execute([]);

        $this->assertEquals(\Symfony\Component\Console\Command\Command::SUCCESS, $tester->getStatusCode());

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Plugin name cannot be empty', $display);
        $this->assertStringContainsString('Plugin name can only contain a-zA-Z0-9', $display);
        $this->assertStringContainsString('Company name cannot be empty', $display);
        $this->assertStringContainsString('Company name can only contain a-zA-Z0-9', $display);
    }

    public function testCreatePluginStructureFileReadError(): void
    {
        $skeletonDir = $this->tempDir . '/skeletons';
        $this->filesystem->mkdir($skeletonDir);
        $skeletonFile = $skeletonDir . '/composer.json.skeleton';
        file_put_contents($skeletonFile, '{$name}');
        chmod($skeletonFile, 0000);

        $command = new class($this->tempDir, $this->filesystem, $skeletonDir) extends VisPluginCreateCommand {
            public function __construct(string $projectDir, Filesystem $filesystem, private string $customSkeletonDir) {
                parent::__construct($projectDir, $filesystem);
            }
            protected function getSkeletonDir(): string {
                return $this->customSkeletonDir;
            }
        };

        $path = $this->tempDir . '/plugins/acme/vis-test-plugin-fail';

        try {
            $class = new \ReflectionClass(VisPluginCreateCommand::class);
            $method = $class->getMethod('createPluginStructure');
            $method->setAccessible(true);
            $method->invokeArgs($command, [$path, 'Test', 'Acme']);

            // Should have skipped the file due to read error
            $this->assertFileDoesNotExist($path . '/composer.json');
        } finally {
            chmod($skeletonFile, 0644);
        }
    }

    public function testExecuteDirectoryExistsAndConfirmDelete(): void
    {
        $pluginDirName = 'plugins/acme/vis-demo-plugin';
        $pluginPath = $this->tempDir . '/' . $pluginDirName;
        $this->filesystem->mkdir($pluginPath);

        $tester = new \Symfony\Component\Console\Tester\CommandTester($this->command);
        // 1. name, 2. company, 3. confirm delete, 4. confirm add bundle, 5. confirm update composer, 6. confirm add routes
        $tester->setInputs(['Demo', 'Acme', 'yes', 'no', 'no', 'no']);
        $tester->execute([]);

        $this->assertDirectoryDoesNotExist($pluginPath);
        $this->assertEquals(\Symfony\Component\Console\Command\Command::SUCCESS, $tester->getStatusCode());
    }

    public function testExecuteDirectoryExistsAndCancel(): void
    {
        $pluginDirName = 'plugins/acme/vis-demo-plugin';
        $pluginPath = $this->tempDir . '/' . $pluginDirName;
        $this->filesystem->mkdir($pluginPath);

        $tester = new \Symfony\Component\Console\Tester\CommandTester($this->command);
        // 1. name, 2. company, 3. deny delete
        $tester->setInputs(['Demo', 'Acme', 'no']);
        $tester->execute([]);

        $this->assertDirectoryExists($pluginPath);
        $this->assertStringContainsString('Creation cancelled.', $tester->getDisplay());
    }
}
