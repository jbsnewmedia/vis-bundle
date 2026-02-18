<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisCoreCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class VisCoreCreateCommandSkeletonMissingCoreTest extends TestCase
{
    private string $tempDir;
    private KernelInterface $kernel;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/vis_core_skeleton_missing_'.uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir.'/src/Controller/Vis');
        $this->filesystem->mkdir($this->tempDir.'/config/packages');

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

    public function testDumpMethodsReturnFalseWhenSkeletonMissing(): void
    {
        $nonExistentSkeletonDir = $this->tempDir.'/missing_skeletons';
        $command = new VisCoreCreateCommand($this->kernel, new Filesystem(), $nonExistentSkeletonDir);

        $this->assertFalse($this->invokePrivate($command, 'dumpMainController', [$this->tempDir.'/src/Controller/Vis/MainController.php']));
        $this->assertFalse($this->invokePrivate($command, 'dumpSecurityController', [$this->tempDir.'/src/Controller/SecurityController.php']));
        $this->assertFalse($this->invokePrivate($command, 'dumpRegistrationController', [$this->tempDir.'/src/Controller/Vis/RegistrationController.php']));
        $this->assertFalse($this->invokePrivate($command, 'dumpLocaleController', [$this->tempDir.'/src/Controller/LocaleController.php']));
        $this->assertFalse($this->invokePrivate($command, 'dumpDarkmodeController', [$this->tempDir.'/src/Controller/Vis/DarkmodeController.php']));
    }

    public function testUpdateVisYamlReturnsFalseWhenSkeletonMissing(): void
    {
        $nonExistentSkeletonDir = $this->tempDir.'/missing_skeletons';
        $command = new VisCoreCreateCommand($this->kernel, new Filesystem(), $nonExistentSkeletonDir);

        $this->assertFalse($this->invokePrivate($command, 'updateVisYaml', ['de,en', 'en']));
    }

    public function testGetSecurityPatchDataWithMissingSkeleton(): void
    {
        $nonExistentSkeletonDir = $this->tempDir.'/missing_skeletons';
        $command = new VisCoreCreateCommand($this->kernel, new Filesystem(), $nonExistentSkeletonDir);

        $result = $this->invokePrivate($command, 'getSecurityPatchData', [$nonExistentSkeletonDir.'/security.yaml.skeleton']);
        $this->assertSame([], $result);
    }
    public function testExecuteFailsWhenSkeletonMissing(): void
    {
        $nonExistentSkeletonDir = $this->tempDir.'/missing_skeletons';
        $command = new VisCoreCreateCommand($this->kernel, new Filesystem(), $nonExistentSkeletonDir);
        $tester = new CommandTester($command);

        // registration = yes -> triggers error in dumpRegistrationController
        $tester->setInputs(['yes', 'no', 'de', 'no', 'en']);
        $status = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Skeleton file not found', $tester->getDisplay());

        $this->assertFalse($this->invokePrivate($command, 'dumpMainController', [$this->tempDir.'/src/Controller/Vis/MainController.php']));
        $this->assertFalse($this->invokePrivate($command, 'dumpSecurityController', [$this->tempDir.'/src/Controller/SecurityController.php']));
    }

    public function testExecuteFailsWhenLocaleSkeletonMissing(): void
    {
        $nonExistentSkeletonDir = $this->tempDir.'/missing_skeletons';
        $command = new VisCoreCreateCommand($this->kernel, new Filesystem(), $nonExistentSkeletonDir);
        $tester = new CommandTester($command);

        // useLocales = true (de,en) -> triggers error in dumpLocaleController
        $tester->setInputs(['no', 'no', 'de,en', 'no', 'en']);
        $status = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Skeleton file not found', $tester->getDisplay());

        $this->assertFalse($this->invokePrivate($command, 'dumpLocaleController', [$this->tempDir.'/src/Controller/LocaleController.php']));
    }

    public function testExecuteFailsWhenDarkmodeSkeletonMissing(): void
    {
        $nonExistentSkeletonDir = $this->tempDir.'/missing_skeletons';
        $command = new VisCoreCreateCommand($this->kernel, new Filesystem(), $nonExistentSkeletonDir);
        $tester = new CommandTester($command);

        // darkmode = yes -> triggers error in dumpDarkmodeController
        $tester->setInputs(['no', 'no', 'de', 'yes', 'en']);
        $status = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Skeleton file not found', $tester->getDisplay());

        $this->assertFalse($this->invokePrivate($command, 'dumpDarkmodeController', [$this->tempDir.'/src/Controller/Vis/DarkmodeController.php']));
    }

    public function testExecuteFailsWhenSecuritySkeletonMissing(): void
    {
        $nonExistentSkeletonDir = $this->tempDir.'/missing_skeletons';
        $command = new VisCoreCreateCommand($this->kernel, new Filesystem(), $nonExistentSkeletonDir);
        $tester = new CommandTester($command);

        // vis_security = yes -> updateSecurityYaml will be called.
        // It calls getSecurityPatchData which now returns [] if skeleton is missing.
        // updateSecurityYaml will then proceed with patching using empty data.
        // BUT updateVisYaml is called later and it WILL fail because vis.yaml.skeleton is also missing!

        $tester->setInputs(['no', 'yes', 'de', 'no', 'en']);

        $status = $tester->execute([]);
        $this->assertSame(Command::FAILURE, $status);
    }

    public function testExecuteFailsWhenVisYamlSkeletonMissing(): void
    {
        $nonExistentSkeletonDir = $this->tempDir.'/missing_skeletons';
        $command = new VisCoreCreateCommand($this->kernel, new Filesystem(), $nonExistentSkeletonDir);
        $tester = new CommandTester($command);

        // updateVisYaml is always called and will fail if skeleton is missing
        $tester->setInputs(['no', 'no', 'de', 'no', 'en']);
        $status = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Skeleton file not found', $tester->getDisplay());

        $this->assertFalse($this->invokePrivate($command, 'updateVisYaml', ['de,en', 'en']));
    }

    public function testUpdateSecurityYamlFailsWhenFileMissing(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);

        // Ensure security.yaml does not exist
        $yamlFile = $this->tempDir.'/config/packages/security.yaml';
        if (file_exists($yamlFile)) {
            unlink($yamlFile);
        }

        $result = $this->invokePrivate($command, 'updateSecurityYaml', [true]);
        $this->assertFalse($result);
    }
}
