<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisPluginCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class VisPluginCreateCommandSkeletonMissingTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_plugin_skeleton_missing_' . uniqid();
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

    public function testCreatePluginStructureSkeletonMissingContinuesGracefully(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);

        $skeletonDir = __DIR__ . '/../../src/Resources/skeleton/plugin';
        $backupDir = __DIR__ . '/../../src/Resources/skeleton/plugin_bak_' . uniqid();

        if (is_dir($skeletonDir)) {
            rename($skeletonDir, $backupDir);
        }

        try {
            $path = $this->tempDir . '/plugins/acme/vis-test-plugin';
            // Should not throw and should simply skip missing skeleton files (continue branch)
            $this->invokePrivate($command, 'createPluginStructure', [$path, 'Test', 'Acme']);

            // No files created because skeleton missing, but directories should exist due to mkdir loop
            $this->assertDirectoryExists($path);
            $this->assertDirectoryExists($path . '/src');
        } finally {
            if (is_dir($backupDir)) {
                rename($backupDir, $skeletonDir);
            }
        }
    }
}
