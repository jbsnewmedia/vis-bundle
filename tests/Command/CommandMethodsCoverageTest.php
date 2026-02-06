<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use JBSNewMedia\VisBundle\Command\VisCoreCreateCommand;
use JBSNewMedia\VisBundle\Command\VisPluginCreateCommand;
use JBSNewMedia\VisBundle\Command\VisProjectCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandMethodsCoverageTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/vis_command_methods_' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);
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

    public function testVisCoreCreateCommandMethods(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);
        $this->filesystem->mkdir($this->tempDir . '/src/Controller/Vis');

        $target = $this->tempDir . '/src/Controller/Vis/MainController.php';
        $this->assertTrue($this->invokePrivate($command, 'dumpMainController', [$target]));

        $target = $this->tempDir . '/src/Controller/SecurityController.php';
        $this->assertTrue($this->invokePrivate($command, 'dumpSecurityController', [$target]));

        $target = $this->tempDir . '/src/Controller/Vis/RegistrationController.php';
        $this->assertTrue($this->invokePrivate($command, 'dumpRegistrationController', [$target]));

        $target = $this->tempDir . '/src/Controller/LocaleController.php';
        $this->assertTrue($this->invokePrivate($command, 'dumpLocaleController', [$target]));
    }

    public function testVisPluginCreateCommandMethods(): void
    {
        $command = new VisPluginCreateCommand($this->tempDir, $this->filesystem);

        $this->filesystem->mkdir($this->tempDir . '/plugins/test');
        $this->invokePrivate($command, 'createPluginStructure', [$this->tempDir . '/plugins/test', 'Test', 'Company']);
        $this->assertFileExists($this->tempDir . '/plugins/test/src/VisTestPluginBundle.php');

        $this->filesystem->mkdir($this->tempDir . '/config');
        file_put_contents($this->tempDir . '/config/bundles.php', "<?php return [];");
        $this->invokePrivate($command, 'addBundleToConfig', ['Test', 'Company']);

        file_put_contents($this->tempDir . '/composer.json', json_encode(['autoload' => ['psr-4' => []]]));
        $this->invokePrivate($command, 'updateRootComposer', ['Test', 'plugins/test', 'Company']);

        file_put_contents($this->tempDir . '/config/routes.yaml', "");
        $this->invokePrivate($command, 'addRoutesToConfig', ['Test', 'plugins/test']);
    }

    public function testVisProjectCreateCommandMethods(): void
    {
        $command = new VisProjectCreateCommand($this->kernel, $this->filesystem);
        $io = $this->createMock(SymfonyStyle::class);

        $this->invokePrivate($command, 'copySkeletonFiles', [$this->tempDir, $io]);

        // Fail updateComposerJson by providing invalid JSON
        file_put_contents($this->tempDir . '/composer.json', "{invalid");
        $this->invokePrivate($command, 'updateComposerJson', [$this->tempDir, $io]);
        $this->assertEquals("{invalid", file_get_contents($this->tempDir . '/composer.json'));

        // Fail patchKernel by not providing the file
        $this->invokePrivate($command, 'patchKernel', [$this->tempDir, $io]);

        $this->filesystem->mkdir($this->tempDir . '/src');
        file_put_contents($this->tempDir . '/src/Kernel.php', "<?php\nclass Kernel {\n use MicroKernelTrait;\n public function __construct() {}\n public function registerBundles() {}\n public function build() {}\n public function configureContainer() {}\n public function configureRoutes() {}\n}");
        $this->invokePrivate($command, 'patchKernel', [$this->tempDir, $io]);

        // Fail patchIndexPhp/patchConsolePhp by not providing the file
        $this->invokePrivate($command, 'patchIndexPhp', [$this->tempDir, $io]);
        $this->invokePrivate($command, 'patchConsolePhp', [$this->tempDir, $io]);

        $this->filesystem->mkdir($this->tempDir . '/public');
        file_put_contents($this->tempDir . '/public/index.php', "return function (array \$context) {\n return new Kernel();\n};");
        $this->invokePrivate($command, 'patchIndexPhp', [$this->tempDir, $io]);
        // Call again to trigger "already patched"
        $this->invokePrivate($command, 'patchIndexPhp', [$this->tempDir, $io]);

        $this->filesystem->mkdir($this->tempDir . '/bin');
        file_put_contents($this->tempDir . '/bin/console', "return function (array \$context) {\n return new Kernel();\n};");
        $this->invokePrivate($command, 'patchConsolePhp', [$this->tempDir, $io]);
        // Call again to trigger "already patched"
        $this->invokePrivate($command, 'patchConsolePhp', [$this->tempDir, $io]);

        $this->assertEquals('abc', $this->invokePrivate($command, 'safePregReplace', ['/x/', 'y', 'abc']));

        try {
            $this->invokePrivate($command, 'safePregReplace', ['/(/', 'y', 'abc']);
            $this->fail('Should have thrown RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Error executing preg_replace', $e->getMessage());
        }
    }

    public function testVisCoreCreateCommandUpdateSecurityYamlExtra(): void
    {
        $command = new VisCoreCreateCommand($this->kernel);
        $file = $this->tempDir . '/config/packages/security.yaml';
        $this->filesystem->mkdir($this->tempDir . '/config/packages');

        // No providers in patch data (using empty skeleton)
        $this->filesystem->dumpFile($this->tempDir . '/skeleton.yaml', "security: {}");
        $patchData = $this->invokePrivate($command, 'getSecurityPatchData', [$this->tempDir . '/skeleton.yaml']);
        $this->assertEmpty($patchData);

        // Coverage for updateSecurityYaml merging
        file_put_contents($file, "security:\n  providers:\n    vis_user_provider: old\n  firewalls:\n    vis: old\n  access_control: []");
        $this->assertTrue($this->invokePrivate($command, 'updateSecurityYaml', [true]));
    }
    public function testJsonKernelPluginLoader(): void
    {
        $loader = new \JBSNewMedia\VisBundle\Core\JsonKernelPluginLoader($this->createMock(\Composer\Autoload\ClassLoader::class), $this->kernel);

        $this->filesystem->mkdir($this->tempDir . '/plugins');
        file_put_contents($this->tempDir . '/plugins/plugins.json', json_encode([['name' => 'Test']]));

        $this->invokePrivate($loader, 'loadPluginInfos');
        $this->assertCount(1, $loader->getPluginInfos());
    }
}
