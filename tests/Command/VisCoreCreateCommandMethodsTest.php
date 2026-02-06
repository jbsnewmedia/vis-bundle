<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisCoreCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class VisCoreCreateCommandMethodsTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_core_methods_' . uniqid();
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

    public function testDumpControllerSuccesses(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);
        $this->filesystem->mkdir($this->tempDir . '/src/Controller/Vis');

        $this->assertTrue($this->invokePrivate($command, 'dumpMainController', [$this->tempDir . '/src/Controller/Vis/MainController.php']));
        $this->assertTrue($this->invokePrivate($command, 'dumpSecurityController', [$this->tempDir . '/src/Controller/SecurityController.php']));
        $this->assertTrue($this->invokePrivate($command, 'dumpRegistrationController', [$this->tempDir . '/src/Controller/Vis/RegistrationController.php']));
        $this->assertTrue($this->invokePrivate($command, 'dumpLocaleController', [$this->tempDir . '/src/Controller/LocaleController.php']));
    }

    public function testDumpControllerFailures(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);

        // Verwende einen Zielpfad, der ein Verzeichnis ist, um deterministisch ein Fehlschlagen auszulösen
        $dirTarget = $this->tempDir . '/is_a_dir_target';
        $this->filesystem->mkdir($dirTarget);

        foreach (['dumpMainController', 'dumpSecurityController', 'dumpRegistrationController', 'dumpLocaleController'] as $method) {
            $this->assertFalse($this->invokePrivate($command, $method, [$dirTarget]));
        }
    }

    public function testUpdateSecurityYamlFullOriginalPath(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/config/packages');
        file_put_contents($this->tempDir . '/config/packages/security.yaml', "security:\n    providers: { }\n    firewalls: { dev: { } }\n    access_control: [ ]\n");

        $command = new VisCoreCreateCommand($this->kernel);
        $result = $this->invokePrivate($command, 'updateSecurityYaml', [true]);
        $this->assertTrue($result);

        $content = file_get_contents($this->tempDir . '/config/packages/security.yaml');
        $this->assertStringContainsString('vis_user_provider', $content);
        $this->assertStringContainsString('vis:', $content);
    }

    public function testUpdateVisYamlFailures(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);
        $this->filesystem->mkdir($this->tempDir . '/config/packages');
        $file = $this->tempDir . '/config/packages/vis.yaml';

        // Make directory read-only to fail dumpFile
        chmod($this->tempDir . '/config/packages', 0555);

        $result = $this->invokePrivate($command, 'updateVisYaml', ['de', 'de']);
        $this->assertFalse($result);

        chmod($this->tempDir . '/config/packages', 0777);
    }

    public function testExecuteFailure(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        // Mock SymfonyStyle questions
        // 1. vis_registration, 2. vis_security, 3. vis_locales, 4. vis_default_locale
        $input->method('isInteractive')->willReturn(true);

        $command = new VisCoreCreateCommand($this->kernel);
        $tester = new CommandTester($command);

        // We make it fail by triggering an error in one of the dump methods
        // Since we can't easily mock __DIR__, we use a file that doesn't exist
        // But the command uses hardcoded __DIR__.
        // Let's use the error property directly via reflection to test the error path in execute
        $ref = new \ReflectionProperty(VisCoreCreateCommand::class, 'error');
        $ref->setAccessible(true);
        $ref->setValue($command, true);
        $refMsg = new \ReflectionProperty(VisCoreCreateCommand::class, 'errorMessages');
        $refMsg->setAccessible(true);
        $refMsg->setValue($command, ['Test Error']);

        $tester->setInputs(['yes', 'yes', 'de,en', 'en']);
        $exitCode = $tester->execute([]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Test Error', $tester->getDisplay());
    }

    public function testDumpMethodsFailures(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);

        // Verwende einen Zielpfad, der ein Verzeichnis ist, um deterministisch ein Fehlschlagen auszulösen
        $dirPath = $this->tempDir . '/is_a_dir_fail';
        $this->filesystem->mkdir($dirPath);

        $this->assertFalse($this->invokePrivate($command, 'dumpMainController', [$dirPath]));
        $this->assertFalse($this->invokePrivate($command, 'dumpSecurityController', [$dirPath]));
        $this->assertFalse($this->invokePrivate($command, 'dumpRegistrationController', [$dirPath]));
        $this->assertFalse($this->invokePrivate($command, 'dumpLocaleController', [$dirPath]));
    }

    public function testDumpMethodsSkeletonNotFound(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);

        $skeletonDir = __DIR__ . '/../../src/Resources/skeleton/core';
        $tempSkeletonDir = __DIR__ . '/../../src/Resources/skeleton/core_bak';

        if (file_exists($skeletonDir)) {
            rename($skeletonDir, $tempSkeletonDir);
            try {
                $target = $this->tempDir . '/test.php';
                $this->assertFalse(@$this->invokePrivate($command, 'dumpMainController', [$target]));
                $this->assertFalse(@$this->invokePrivate($command, 'dumpSecurityController', [$target]));
                $this->assertFalse(@$this->invokePrivate($command, 'dumpRegistrationController', [$target]));
                $this->assertFalse(@$this->invokePrivate($command, 'dumpLocaleController', [$target]));
                $this->assertFalse(@$this->invokePrivate($command, 'updateVisYaml', ['de', 'de']));
            } finally {
                rename($tempSkeletonDir, $skeletonDir);
            }
        }
    }

    public function testDumpMethodsFileNotCreatedPostDump(): void
    {
        // Wir nutzen ein Mock-Filesystem, das bei exists() false zurückgibt, nachdem dumpFile() aufgerufen wurde.
        $mockFS = $this->createMock(Filesystem::class);
        $mockFS->method('exists')->willReturn(false);

        $command = new VisCoreCreateCommand($this->kernel, $mockFS);
        $target = $this->tempDir . '/virtual_file.php';

        $this->assertFalse($this->invokePrivate($command, 'dumpMainController', [$target]));
        $this->assertFalse($this->invokePrivate($command, 'dumpSecurityController', [$target]));
        $this->assertFalse($this->invokePrivate($command, 'dumpRegistrationController', [$target]));
        $this->assertFalse($this->invokePrivate($command, 'dumpLocaleController', [$target]));
    }

    public function testUpdateSecurityYamlAccessControlNotArray(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);
        $file = $this->tempDir . '/config/packages/security.yaml';
        $this->filesystem->mkdir($this->tempDir . '/config/packages');

        file_put_contents($file, "security: { access_control: { not: array } }");
        $this->assertTrue($this->invokePrivate($command, 'updateSecurityYaml', [true]));
    }

}
