<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisCoreCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class VisCoreCreateCommandUpdateSecurityYamlTest extends TestCase
{
    private string $tempDir;
    private KernelInterface $kernel;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/vis_core_security_'.uniqid();
        $this->filesystem = new Filesystem();
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

    public function testUpdateSecurityYamlFirewallInsertionAfterDev(): void
    {
        $yamlFile = $this->tempDir.'/config/packages/security.yaml';
        $content = <<<YAML
security:
    providers:
        users_in_memory: { memory: null }
    firewalls:
        dev: { }
        main: { }
YAML;
        $this->filesystem->dumpFile($yamlFile, $content);

        $command = new VisCoreCreateCommand($this->kernel);
        $this->invokePrivate($command, 'updateSecurityYaml', [true]);

        $data = \Symfony\Component\Yaml\Yaml::parseFile($yamlFile);
        $keys = array_keys($data['security']['firewalls']);

        // Expected order: dev, vis, main
        $this->assertSame(['dev', 'vis', 'main'], $keys);

        // Check providers
        $this->assertArrayHasKey('vis_user_provider', $data['security']['providers']);
    }

    public function testUpdateSecurityYamlFirewallInsertionNoDev(): void
    {
        $yamlFile = $this->tempDir.'/config/packages/security.yaml';
        $content = <<<YAML
security:
    firewalls:
        main: { }
YAML;
        $this->filesystem->dumpFile($yamlFile, $content);

        $command = new VisCoreCreateCommand($this->kernel);
        $this->invokePrivate($command, 'updateSecurityYaml', [true]);

        $data = \Symfony\Component\Yaml\Yaml::parseFile($yamlFile);
        $keys = array_keys($data['security']['firewalls']);

        // Expected order: main, vis
        $this->assertSame(['main', 'vis'], $keys);
    }

    public function testUpdateSecurityYamlNoLocales(): void
    {
        $yamlFile = $this->tempDir.'/config/packages/security.yaml';
        $this->filesystem->dumpFile($yamlFile, "security: { providers: { } }");

        $command = new VisCoreCreateCommand($this->kernel);
        // useLocales = false
        $this->invokePrivate($command, 'updateSecurityYaml', [false]);

        $data = \Symfony\Component\Yaml\Yaml::parseFile($yamlFile);

        // Check access_control
        $paths = array_column($data['security']['access_control'], 'path');
        $this->assertNotContains('^/vis/api', $paths);
    }

    public function testUpdateSecurityYamlMissingSectionsInYaml(): void
    {
        $yamlFile = $this->tempDir.'/config/packages/security.yaml';
        // Minimal valid security.yaml without providers or firewalls or access_control
        $this->filesystem->dumpFile($yamlFile, "security: { }");

        $command = new VisCoreCreateCommand($this->kernel);
        $this->invokePrivate($command, 'updateSecurityYaml', [true]);

        $data = \Symfony\Component\Yaml\Yaml::parseFile($yamlFile);
        $this->assertArrayHasKey('providers', $data['security']);
        $this->assertArrayHasKey('firewalls', $data['security']);
        $this->assertArrayHasKey('access_control', $data['security']);
    }
}
