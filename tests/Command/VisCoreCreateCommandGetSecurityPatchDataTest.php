<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisCoreCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class VisCoreCreateCommandGetSecurityPatchDataTest extends TestCase
{
    public function testGetSecurityPatchDataReadsSkeletonFile(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        // projectDir not used in this test
        $kernel->method('getProjectDir')->willReturn(sys_get_temp_dir());

        $command = new VisCoreCreateCommand($kernel);

        $ref = new \ReflectionMethod(VisCoreCreateCommand::class, 'getSecurityPatchData');
        $ref->setAccessible(true);
        $skeletonFile = __DIR__ . '/../../src/Resources/skeleton/core/security.yaml.skeleton';

        $result = $ref->invoke($command, $skeletonFile);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('providers', $result);
        $this->assertArrayHasKey('firewalls', $result);
        $this->assertArrayHasKey('access_control', $result);
    }
}
