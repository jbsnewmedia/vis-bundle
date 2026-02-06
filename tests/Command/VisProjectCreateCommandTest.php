<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisProjectCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class VisProjectCreateCommandTest extends TestCase
{
    private string $tempDir;
    private KernelInterface $kernel;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_project_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir . '/src');
        $this->filesystem->mkdir($this->tempDir . '/public');
        $this->filesystem->mkdir($this->tempDir . '/bin');

        // Dummy files to patch
        file_put_contents($this->tempDir . '/composer.json', json_encode(['require' => [], 'require-dev' => [], 'scripts' => []]));
        file_put_contents($this->tempDir . '/src/Kernel.php', "<?php\nclass Kernel extends BaseKernel {\n    use MicroKernelTrait;\n}\n");
        file_put_contents($this->tempDir . '/public/index.php', "<?php\nreturn function (array \$context) {\n    return new Kernel(\$context['APP_ENV'], (bool) \$context['APP_DEBUG']);\n};\n");
        file_put_contents($this->tempDir . '/bin/console', "<?php\nreturn function (array \$context) {\n    \$kernel = new Kernel(\$context['APP_ENV'], (bool) \$context['APP_DEBUG']);\n    return new Application(\$kernel);\n};\n");

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getProjectDir')->willReturn($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testExecuteSuccess(): void
    {
        // For readonly properties, we need to create the object differently or mock the constructor
        // But the constructor is what sets it.
        // We can't use reflection on readonly property in PHP 8.1+.
        // Let's try to mock Kernel and then pass it, but VisProjectCreateCommand uses __DIR__ in constructor.

        // Actually, since we are in the bundle project itself, we can use the real skeleton files!
        // We just need to ensure the projectDir has the expected structure.

        $this->filesystem->mkdir($this->tempDir . '/src');
        $this->filesystem->mkdir($this->tempDir . '/config');
        $this->filesystem->mkdir($this->tempDir . '/public');
        $this->filesystem->mkdir($this->tempDir . '/bin');

        // Dummy composer.json
        file_put_contents($this->tempDir . '/composer.json', json_encode([
            'require' => ['symfony/console' => '^6.0'],
            'autoload' => ['psr-4' => ['App\\' => 'src/']]
        ]));

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $commandTester = new CommandTester($command);

        // Input: confirm (yes)
        $commandTester->setInputs(['yes']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Vis project structure has been initialized successfully', $commandTester->getDisplay());

        // Check for created files (they should be copied from real skeleton)
        $this->assertFileExists($this->tempDir . '/phpstan-global.neon');
        $this->assertFileExists($this->tempDir . '/rector.php');
        $this->assertDirectoryExists($this->tempDir . '/plugins');

        // Check composer.json
        $composerData = json_decode(file_get_contents($this->tempDir . '/composer.json'), true);
        $this->assertArrayHasKey('scripts', $composerData);
    }

    public function testPatchingWithAlreadyPatchedFiles(): void
    {
        // If files already contain the markers, they should not be patched again (or at least not corrupted)
        file_put_contents($this->tempDir . '/src/Kernel.php', "ClassLoader");

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $kernelContent = file_get_contents($this->tempDir . '/src/Kernel.php');
        $this->assertEquals("ClassLoader", $kernelContent);
    }

    public function testExecuteCancelled(): void
    {
        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $commandTester = new CommandTester($command);

        // Input: confirm (no)
        $commandTester->setInputs(['no']);

        $exitCode = $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Operation cancelled', $commandTester->getDisplay());
    }
}
