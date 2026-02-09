<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisCoreCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class VisCoreCreateCommandUpdateSecurityYamlEdgeTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_core_update_security_yaml_edge_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir . '/config/packages');

        // Minimal valid security.yaml
        file_put_contents($this->tempDir . '/config/packages/security.yaml', "security: { }\n");
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

    public function testUpdateSecurityYamlWithInvalidAccessControlEntries(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->tempDir);

        // Anonymous subclass to override getSecurityPatchData
        $command = new class($kernel) extends VisCoreCreateCommand {
            protected function getSecurityPatchData(string $skeletonFile): array
            {
                return [
                    'access_control' => [
                        'not-an-array',            // should trigger continue (non-array)
                        ['roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'], // missing path -> continue
                        ['path' => '^/new', 'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'], // valid
                    ],
                ];
            }
        };

        $result = $this->invokePrivate($command, 'updateSecurityYaml', [false]);
        $this->assertTrue($result);

        $content = file_get_contents($this->tempDir . '/config/packages/security.yaml');
        $this->assertIsString($content);
        $this->assertStringContainsString('^/new', (string) $content);
    }
}
