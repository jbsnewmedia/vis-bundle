<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisPluginCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class VisPluginCreateCommandMethodsFailuresTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_plugin_methods_fail_' . uniqid();
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

    public function testAddBundleToConfigFailures(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);

        $this->filesystem->mkdir($this->tempDir . '/config');
        $file = $this->tempDir . '/config/bundles.php';

        // 1. File not found (already covered but for completeness)
        $this->invokePrivate($command, 'addBundleToConfig', ['T', 'C']);
        $this->assertFileDoesNotExist($file);

        // 2. File unreadable
        file_put_contents($file, "<?php return [];");
        chmod($file, 0000);
        try {
            @$this->invokePrivate($command, 'addBundleToConfig', ['T', 'C']);
        } finally {
            chmod($file, 0644);
        }
        $this->assertStringNotContainsString('VisTPluginBundle', file_get_contents($file));
    }

    public function testUpdateRootComposerFailures(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $file = $this->tempDir . '/composer.json';

        // 1. File unreadable
        file_put_contents($file, "{}");
        chmod($file, 0000);
        try {
            @$this->invokePrivate($command, 'updateRootComposer', ['T', 'P', 'C']);
        } finally {
            chmod($file, 0644);
        }
        $this->assertEquals("{}", file_get_contents($file));

        // 2. Invalid JSON (already covered in other file but keep here)
        file_put_contents($file, "{invalid");
        $this->invokePrivate($command, 'updateRootComposer', ['T', 'P', 'C']);
        $this->assertEquals("{invalid", file_get_contents($file));
    }

    public function testAddRoutesToConfigFailures(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $this->filesystem->mkdir($this->tempDir . '/config');
        $file = $this->tempDir . '/config/routes.yaml';

        // 1. File unreadable
        file_put_contents($file, "old: {}");
        chmod($file, 0000);
        try {
            @$this->invokePrivate($command, 'addRoutesToConfig', ['T', 'P']);
        } finally {
            chmod($file, 0644);
        }
        $this->assertEquals("old: {}", file_get_contents($file));
    }
}
