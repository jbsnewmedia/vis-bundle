<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisProjectCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VisProjectCreateCommandPrivateTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private VisProjectCreateCommand $command;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_project_private_test_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir . '/src');
        $this->filesystem->mkdir($this->tempDir . '/config');
        $this->filesystem->mkdir($this->tempDir . '/public');
        $this->filesystem->mkdir($this->tempDir . '/bin');

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getProjectDir')->willReturn($this->tempDir);

        $this->command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    private function callMethod(string $name, array $args): mixed
    {
        $class = new \ReflectionClass(VisProjectCreateCommand::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->command, $args);
    }

    public function testUpdateComposerJson(): void
    {
        $io = $this->createMock(SymfonyStyle::class);

        file_put_contents($this->tempDir . '/composer.json', json_encode([
            'require' => [],
            'require-dev' => [],
            'config' => [],
            'scripts' => []
        ]));

        $this->callMethod('updateComposerJson', [$this->tempDir, $io]);

        $composer = json_decode(file_get_contents($this->tempDir . '/composer.json'), true);
        $this->assertArrayHasKey('scripts', $composer);
        $this->assertArrayHasKey('bin-rector', $composer['scripts']);
    }

    public function testPatchKernel(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        file_put_contents($this->tempDir . '/src/Kernel.php', "<?php\nnamespace App;\nclass Kernel extends BaseKernel {\n    use MicroKernelTrait;\n}\n");

        $this->callMethod('patchKernel', [$this->tempDir, $io]);

        $content = file_get_contents($this->tempDir . '/src/Kernel.php');
        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('use Composer\Autoload\ClassLoader;', $content);
        $this->assertStringContainsString('private readonly JsonKernelPluginLoader $pluginLoader;', $content);
        $this->assertStringContainsString('public function __construct', $content);
        $this->assertStringContainsString('public function registerBundles()', $content);
    }

    public function testPatchIndexPhp(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        file_put_contents($this->tempDir . '/public/index.php', "<?php\nreturn function (array \$context) {\n    return new Kernel(\$context['APP_ENV'], (bool) \$context['APP_DEBUG']);\n};\n");

        $this->callMethod('patchIndexPhp', [$this->tempDir, $io]);

        $content = file_get_contents($this->tempDir . '/public/index.php');
        $this->assertStringContainsString('$classLoader = require __DIR__.\'/../vendor/autoload.php\';', $content);
        $this->assertStringContainsString('new Kernel($context[\'APP_ENV\'], (bool) $context[\'APP_DEBUG\'], $classLoader)', $content);
    }

    public function testPatchConsolePhp(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        file_put_contents($this->tempDir . '/bin/console', "<?php\nreturn function (array \$context) {\n    return new Kernel(\$context['APP_ENV'], (bool) \$context['APP_DEBUG']);\n};\n");

        $this->callMethod('patchConsolePhp', [$this->tempDir, $io]);

        $content = file_get_contents($this->tempDir . '/bin/console');
        $this->assertStringContainsString('$classLoader = require __DIR__.\'/../vendor/autoload.php\';', $content);
        $this->assertStringContainsString('new Kernel($context[\'APP_ENV\'], (bool) $context[\'APP_DEBUG\'], $classLoader)', $content);
    }

    public function testUpdateComposerJsonMissingSections(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $file = $this->tempDir . '/composer.json';
        file_put_contents($file, json_encode(['require' => []]));

        $skeletonFile = $this->tempDir . '/composer.json';
        file_put_contents($skeletonFile, json_encode(['scripts' => ['test' => 'ls']]));

        $ref = new \ReflectionProperty(VisProjectCreateCommand::class, 'skeletonDir');
        $ref->setAccessible(true);
        $ref->setValue($this->command, $this->tempDir);

        $this->callMethod('updateComposerJson', [$this->tempDir, $io]);
        $composer = json_decode(file_get_contents($file), true);
        $this->assertArrayHasKey('scripts', $composer);
    }

    public function testUpdateComposerJsonReadError(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('error')->with('Failed to read composer.json');

        $file = $this->tempDir . '/composer.json';
        file_put_contents($file, json_encode([]));

        $skeletonFile = $this->tempDir . '/composer.json';
        file_put_contents($skeletonFile, json_encode([]));

        $ref = new \ReflectionProperty(VisProjectCreateCommand::class, 'skeletonDir');
        $ref->setAccessible(true);
        $ref->setValue($this->command, $this->tempDir);

        chmod($file, 0000);
        try {
            $this->callMethod('updateComposerJson', [$this->tempDir, $io]);
        } finally {
            chmod($file, 0644);
        }
    }

    public function testUpdateComposerJsonParseError(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('error')->with('Failed to parse composer.json');

        $file = $this->tempDir . '/composer.json';
        file_put_contents($file, '"this-is-not-an-array"');

        $skeletonFile = $this->tempDir . '/composer.json.skeleton';
        file_put_contents($skeletonFile, json_encode(['scripts' => []]));

        $ref = new \ReflectionProperty(VisProjectCreateCommand::class, 'skeletonDir');
        $ref->setAccessible(true);
        $ref->setValue($this->command, $this->tempDir);

        $this->callMethod('updateComposerJson', [$this->tempDir, $io]);
    }

    public function testUpdateComposerJsonFilesNotFound(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        // Files do not exist in the non_existent_dir
        $this->callMethod('updateComposerJson', [$this->tempDir . '/non_existent_dir', $io]);
        $this->assertTrue(true); // Should return early
    }

    public function testPatchKernelFileNotFound(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('error')->with('src/Kernel.php not found. Cannot patch.');

        $this->callMethod('patchKernel', [$this->tempDir . '/non_existent', $io]);
    }

    public function testPatchKernelReadError(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('error')->with('Failed to read src/Kernel.php.');

        $file = $this->tempDir . '/src/Kernel.php';
        file_put_contents($file, '<?php');
        chmod($file, 0000);
        try {
            $this->callMethod('patchKernel', [$this->tempDir, $io]);
        } finally {
            chmod($file, 0644);
        }
    }

    public function testPatchKernelWarningPaths(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->exactly(5))->method('warning');

        // Kernel with all methods already present
        $content = "<?php\nnamespace App;\nclass Kernel extends BaseKernel {\n";
        $content .= "    use MicroKernelTrait;\n";
        $content .= "    private readonly JsonKernelPluginLoader \$pluginLoader;\n";
        $content .= "    public function __construct() { }\n";
        $content .= "    public function registerBundles() { }\n";
        $content .= "    protected function build() { }\n";
        $content .= "    protected function configureContainer() { }\n";
        $content .= "    protected function configureRoutes() { }\n";
        $content .= "}\n";

        file_put_contents($this->tempDir . '/src/Kernel.php', $content);

        $this->callMethod('patchKernel', [$this->tempDir, $io]);
    }

    public function testSafePregReplaceError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->callMethod('safePregReplace', ['/(/', 'rep', 'sub']);
    }

    public function testPatchIndexPhpReadError(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('error')->with('Failed to read public/index.php');

        $file = $this->tempDir . '/public/index.php';
        file_put_contents($file, 'dummy');
        chmod($file, 0000);

        try {
            $this->callMethod('patchIndexPhp', [$this->tempDir, $io]);
        } finally {
            chmod($file, 0644);
        }
    }

    public function testPatchConsolePhpReadError(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('error')->with('Failed to read bin/console');

        $file = $this->tempDir . '/bin/console';
        file_put_contents($file, 'dummy');
        chmod($file, 0000);

        try {
            $this->callMethod('patchConsolePhp', [$this->tempDir, $io]);
        } finally {
            chmod($file, 0644);
        }
    }

    public function testPatchIndexPhpNoReturnFunction(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with('Could not find return function in public/index.php. Please patch manually.');

        file_put_contents($this->tempDir . '/public/index.php', "<?php\n echo 'no return function';");

        $this->callMethod('patchIndexPhp', [$this->tempDir, $io]);
    }

    public function testPatchConsolePhpNoReturnFunction(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with('Could not find return function in bin/console. Please patch manually.');

        file_put_contents($this->tempDir . '/bin/console', "<?php\n echo 'no return function';");

        $this->callMethod('patchConsolePhp', [$this->tempDir, $io]);
    }

    public function testPatchIndexPhpAlreadyPatched(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with($this->stringContains('already seems to load ClassLoader'));
        file_put_contents($this->tempDir . '/public/index.php', "\$classLoader = require __DIR__.'/../vendor/autoload.php';");

        $this->callMethod('patchIndexPhp', [$this->tempDir, $io]);
    }

    public function testPatchConsolePhpAlreadyPatched(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with($this->stringContains('already seems to load ClassLoader'));
        file_put_contents($this->tempDir . '/bin/console', "\$classLoader = require __DIR__.'/../vendor/autoload.php';");

        $this->callMethod('patchConsolePhp', [$this->tempDir, $io]);
    }

    public function testPatchIndexPhpNoFunction(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with($this->stringContains('Could not find return function'));
        file_put_contents($this->tempDir . '/public/index.php', "<?php echo 'hello';");

        $this->callMethod('patchIndexPhp', [$this->tempDir, $io]);
    }

    public function testPatchConsolePhpNoFunction(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with($this->stringContains('Could not find return function'));
        file_put_contents($this->tempDir . '/bin/console', "<?php echo 'hello';");

        $this->callMethod('patchConsolePhp', [$this->tempDir, $io]);
    }

    public function testPatchIndexPhpNoFile(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with($this->stringContains('not found. Skipping patch.'));
        unlink($this->tempDir . '/public/index.php');

        $this->callMethod('patchIndexPhp', [$this->tempDir, $io]);
    }

    public function testPatchConsolePhpNoFile(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with($this->stringContains('not found. Skipping patch.'));
        unlink($this->tempDir . '/bin/console');

        $this->callMethod('patchConsolePhp', [$this->tempDir, $io]);
    }

    public function testUpdateComposerJsonInvalidJson(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        file_put_contents($this->tempDir . '/composer.json', "{invalid");
        $this->callMethod('updateComposerJson', [$this->tempDir, $io]);
        $content = file_get_contents($this->tempDir . '/composer.json');
        $this->assertEquals("{invalid", $content);
    }

    public function testPatchMethodsNoFiles(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        // Kernel::patchKernel does NOT have a warning for file not found
        // patchIndexPhp and patchConsolePhp DO have warnings.
        $io->expects($this->exactly(2))->method('warning')->with($this->stringContains('not found. Skipping patch.'));

        $this->callMethod('patchKernel', [$this->tempDir, $io]);
        $this->callMethod('patchIndexPhp', [$this->tempDir, $io]);
        $this->callMethod('patchConsolePhp', [$this->tempDir, $io]);
    }

    public function testPatchIndexPhpReadFailure(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $this->filesystem->mkdir($this->tempDir . '/public');
        $indexFile = $this->tempDir . '/public/index.php';
        file_put_contents($indexFile, "test");
        chmod($indexFile, 0000);

        $io->expects($this->once())->method('error')->with('Failed to read public/index.php');

        try {
            @$this->callMethod('patchIndexPhp', [$this->tempDir, $io]);
        } finally {
            chmod($indexFile, 0644);
        }
    }

    public function testPatchConsolePhpReadFailure(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $this->filesystem->mkdir($this->tempDir . '/bin');
        $consoleFile = $this->tempDir . '/bin/console';
        file_put_contents($consoleFile, "test");
        chmod($consoleFile, 0000);

        $io->expects($this->once())->method('error')->with('Failed to read bin/console');

        try {
            @$this->callMethod('patchConsolePhp', [$this->tempDir, $io]);
        } finally {
            chmod($consoleFile, 0644);
        }
    }
}
