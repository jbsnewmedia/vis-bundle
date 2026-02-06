<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisProjectCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VisProjectCreateCommandCoverageTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_project_create_coverage_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getProjectDir')->willReturn($this->tempDir);
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

    public function testPatchKernelFileNotFound(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('error')->with('src/Kernel.php not found. Cannot patch.');

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $this->invokePrivate($command, 'patchKernel', [$this->tempDir, $io]);
    }

    public function testPatchIndexPhpFileNotFound(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with('public/index.php not found. Skipping patch.');

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $this->invokePrivate($command, 'patchIndexPhp', [$this->tempDir, $io]);
    }

    public function testPatchConsolePhpFileNotFound(): void
    {
        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with('bin/console not found. Skipping patch.');

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $this->invokePrivate($command, 'patchConsolePhp', [$this->tempDir, $io]);
    }

    public function testPatchIndexPhpAlreadyPatched(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/public');
        $file = $this->tempDir . '/public/index.php';
        file_put_contents($file, '$classLoader = require');

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with('public/index.php already seems to load ClassLoader.');

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $this->invokePrivate($command, 'patchIndexPhp', [$this->tempDir, $io]);
    }

    public function testPatchIndexPhpNoReturnFunction(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/public');
        $file = $this->tempDir . '/public/index.php';
        file_put_contents($file, '<?php echo "hello";');

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with('Could not find return function in public/index.php. Please patch manually.');

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $this->invokePrivate($command, 'patchIndexPhp', [$this->tempDir, $io]);
    }

    public function testPatchConsolePhpAlreadyPatched(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/bin');
        $file = $this->tempDir . '/bin/console';
        file_put_contents($file, '$classLoader = require');

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with('bin/console already seems to load ClassLoader.');

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $this->invokePrivate($command, 'patchConsolePhp', [$this->tempDir, $io]);
    }

    public function testPatchConsolePhpNoReturnFunction(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/bin');
        $file = $this->tempDir . '/bin/console';
        file_put_contents($file, '<?php echo "hello";');

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->once())->method('warning')->with('Could not find return function in bin/console. Please patch manually.');

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $this->invokePrivate($command, 'patchConsolePhp', [$this->tempDir, $io]);
    }

    public function testPatchKernelWarnings(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/src');
        $file = $this->tempDir . '/src/Kernel.php';
        file_put_contents($file, "<?php\nclass Kernel {\n public function __construct() {}\n public function registerBundles() {}\n public function build() {}\n public function configureContainer() {}\n public function configureRoutes() {}\n}");

        $io = $this->createMock(SymfonyStyle::class);
        // Expect several warnings
        $io->expects($this->atLeastOnce())->method('warning');

        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $this->invokePrivate($command, 'patchKernel', [$this->tempDir, $io]);
    }
}
