<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisCoreCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

final class VisCoreCreateCommandPrivateTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/vis_core_create_private_'.uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);
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

    public function testDumpControllerSkeletonsViaPrivateMethods(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);

        // MainController
        $target = $this->tempDir.'/src/Controller/Vis/MainController.php';
        $result = $this->invokePrivate($command, 'dumpMainController', [$target]);
        $this->assertTrue($result);
        $this->assertFileExists($target);

        // SecurityController
        $target2 = $this->tempDir.'/src/Controller/SecurityController.php';
        $result2 = $this->invokePrivate($command, 'dumpSecurityController', [$target2]);
        $this->assertTrue($result2);
        $this->assertFileExists($target2);

        // RegistrationController
        $target3 = $this->tempDir.'/src/Controller/Vis/RegistrationController.php';
        $result3 = $this->invokePrivate($command, 'dumpRegistrationController', [$target3]);
        $this->assertTrue($result3);
        $this->assertFileExists($target3);

        // LocaleController
        $target4 = $this->tempDir.'/src/Controller/LocaleController.php';
        $result4 = $this->invokePrivate($command, 'dumpLocaleController', [$target4]);
        $this->assertTrue($result4);
        $this->assertFileExists($target4);
    }

    public function testUpdateVisYamlAndSecurityYamlViaPrivateMethods(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);

        // updateVisYaml
        $visYaml = $this->tempDir.'/config/packages/vis.yaml';
        // Mock skeleton file location or ensure it's found.
        // The command uses __DIR__.'/../Resources/skeleton/core/vis.yaml.skeleton'

        $result = $this->invokePrivate($command, 'updateVisYaml', ['de,en', 'en']);
        $this->assertTrue($result);
        $this->assertFileExists($visYaml);
        $content = file_get_contents($visYaml);
        $this->assertIsString($content);
        $this->assertStringContainsString('vis:', $content);

        // Prepare a minimal security.yaml and call updateSecurityYaml
        $securityYaml = $this->tempDir.'/config/packages/security.yaml';
        $this->filesystem->dumpFile($securityYaml, "security:\n    providers: {}\n    firewalls: {}\n");

        $result2 = $this->invokePrivate($command, 'updateSecurityYaml', [true]);
        $this->assertTrue($result2);
        $this->assertFileExists($securityYaml);

        $secContent = file_get_contents($securityYaml);
        $this->assertIsString($secContent);
        $this->assertStringContainsString('firewalls:', $secContent);
        $this->assertStringContainsString('custom_authenticator: JBSNewMedia\\VisBundle\\Security\\VisAuthenticator', $secContent);
    }

    public function testUpdateSecurityYamlNoFirewalls(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);
        $securityYaml = $this->tempDir.'/config/packages/security.yaml';
        $this->filesystem->dumpFile($securityYaml, "security: { providers: {} }");

        $result = $this->invokePrivate($command, 'updateSecurityYaml', [true]);
        $this->assertTrue($result);
        $content = file_get_contents($securityYaml);
        // In current implementation, if 'firewalls' is missing, it adds it if 'security' key exists.
        $this->assertStringContainsString('firewalls:', $content);
    }

    public function testGetSecurityPatchData(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);
        // Path relative to this test file to the real skeleton
        $skeletonFile = __DIR__.'/../../src/Resources/skeleton/core/security.yaml.skeleton';

        $result = $this->invokePrivate($command, 'getSecurityPatchData', [$skeletonFile]);
        if (file_exists($skeletonFile)) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('providers', $result);
        }
    }
}
