<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisPluginCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;

class VisPluginCreateCommandCoverageTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_plugin_create_coverage_' . uniqid();
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

    public function testAddBundleToConfigNoFile(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        // bundles.php doesn't exist
        $this->invokePrivate($command, 'addBundleToConfig', ['Test', 'Company']);
        $this->assertFileDoesNotExist($this->tempDir . '/config/bundles.php');
    }

    public function testUpdateRootComposerNoFile(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        // composer.json doesn't exist
        $this->invokePrivate($command, 'updateRootComposer', ['Test', 'plugins/test', 'Company']);
        $this->assertFileDoesNotExist($this->tempDir . '/composer.json');
    }

    public function testAddRoutesToConfigNoFile(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        // routes.yaml doesn't exist
        $this->invokePrivate($command, 'addRoutesToConfig', ['Test', 'plugins/test']);
        $this->assertFileDoesNotExist($this->tempDir . '/config/routes.yaml');
    }

    public function testAddBundleToConfigAlreadyExists(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/config');
        $file = $this->tempDir . '/config/bundles.php';
        $bundleClass = 'Company\\VisTestPluginBundle\\VisTestPluginBundle';
        file_put_contents($file, "<?php return [ " . $bundleClass . "::class => ['all' => true] ];");

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $this->invokePrivate($command, 'addBundleToConfig', ['Test', 'Company']);

        $content = file_get_contents($file);
        $this->assertEquals(1, substr_count($content, $bundleClass));
    }

    public function testUpdateRootComposerInvalidJson(): void
    {
        $file = $this->tempDir . '/composer.json';
        file_put_contents($file, "{invalid");

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $this->invokePrivate($command, 'updateRootComposer', ['Test', 'plugins/test', 'Company']);

        $this->assertEquals("{invalid", file_get_contents($file));
    }

    public function testAddRoutesToConfigAlreadyExists(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/config');
        $file = $this->tempDir . '/config/routes.yaml';
        file_put_contents($file, "vis_test_plugin: {}");

        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);
        $this->invokePrivate($command, 'addRoutesToConfig', ['Test', 'plugins/test']);

        $this->assertEquals("vis_test_plugin: {}", file_get_contents($file));
    }
}
