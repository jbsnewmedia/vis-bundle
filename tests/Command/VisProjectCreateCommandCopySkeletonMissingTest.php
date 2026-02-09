<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisProjectCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VisProjectCreateCommandCopySkeletonMissingTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_project_copy_skeleton_missing_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);
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

    public function testCopySkeletonFilesMissingDirectoryGraceful(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->tempDir);

        $command = new VisProjectCreateCommand($kernel, $this->filesystem);

        $io = $this->createMock(SymfonyStyle::class);
        $io->expects($this->atLeast(0))->method($this->anything());

        $skeletonDir = __DIR__ . '/../../src/Resources/skeleton/project';
        $backupDir = __DIR__ . '/../../src/Resources/skeleton/project_bak_' . uniqid();

        if (is_dir($skeletonDir)) {
            rename($skeletonDir, $backupDir);
        }

        try {
            // Should not throw despite missing skeleton files
            $this->invokePrivate($command, 'copySkeletonFiles', [$this->tempDir, $io]);
            $this->assertTrue(true);
        } finally {
            if (is_dir($backupDir)) {
                rename($backupDir, $skeletonDir);
            }
        }
    }
}
