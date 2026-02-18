<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisCoreCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class VisCoreCreateCommandDarkmodeTest extends TestCase
{
    private string $tempDir;
    private KernelInterface $kernel;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/vis_core_darkmode_'.uniqid();
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

    public function testExecuteCreatesDarkmodeControllerWhenEnabled(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);
        $tester = new CommandTester($command);

        // Inputs: registration (no), update security.yaml (no), languages (de,en -> ensures locales array), darkmode (yes), default language (en)
        $tester->setInputs(['no', 'no', 'de,en', 'yes', 'en']);
        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertFileExists($this->tempDir.'/src/Controller/Vis/MainController.php');
        $this->assertFileExists($this->tempDir.'/src/Controller/SecurityController.php');
        $this->assertFileExists($this->tempDir.'/src/Controller/LocaleController.php');
        $this->assertFileExists($this->tempDir.'/src/Controller/Vis/DarkmodeController.php');
        $this->assertFileDoesNotExist($this->tempDir.'/src/Controller/Vis/RegistrationController.php');
    }
}
