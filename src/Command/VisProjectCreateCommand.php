<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'vis:project:create',
    description: 'Initializes a new vis project structure',
)]
class VisProjectCreateCommand extends Command
{
    private string $skeletonDir;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
        $this->skeletonDir = __DIR__.'/../Resources/skeleton/project';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectDir = $this->kernel->getProjectDir();

        $io->title('Vis Project Initialization');

        if (!$io->confirm('This will copy skeleton files and patch your src/Kernel.php. Do you want to continue?', true)) {
            $io->warning('Operation cancelled.');

            return Command::SUCCESS;
        }

        $this->copySkeletonFiles($projectDir, $io);
        $this->updateComposerJson($projectDir, $io);
        $this->patchKernel($projectDir, $io);
        $this->patchIndexPhp($projectDir, $io);
        $this->patchConsolePhp($projectDir, $io);

        $io->success('Vis project structure has been initialized successfully.');
        $io->note('Please check src/Kernel.php and ensure that the ClassLoader is correctly passed to the Kernel constructor in public/index.php and bin/console. See example:');
        $io->writeln([
            '    return function (array $context) {',
            '        $classLoader = require __DIR__.\'/../vendor/autoload.php\';',
            '        $kernel = new Kernel($context[\'APP_ENV\'], (bool) $context[\'APP_DEBUG\'], $classLoader);',
            '',
            '        return new Application($kernel); // or return $kernel; for index.php',
            '    };',
        ]);

        return Command::SUCCESS;
    }

    private function copySkeletonFiles(string $projectDir, SymfonyStyle $io): void
    {
        $io->section('Copying skeleton files');

        // Copy root files
        $rootFiles = [
            'phpstan-global.neon' => 'phpstan-global.neon',
            'rector.php.skeleton' => 'rector.php',
            '.php-cs-fixer.dist.php.skeleton' => '.php-cs-fixer.dist.php',
            '.editorconfig' => '.editorconfig',
            '.gitattributes' => '.gitattributes',
        ];
        foreach ($rootFiles as $skeletonFile => $targetFile) {
            $fullSkeletonPath = $this->skeletonDir.'/'.$skeletonFile;
            if ($this->filesystem->exists($fullSkeletonPath)) {
                $this->filesystem->copy($fullSkeletonPath, $projectDir.'/'.$targetFile, true);
                $io->writeln('Copied '.$targetFile);
            }
        }

        // Ensure plugins directory exists
        if (!$this->filesystem->exists($projectDir.'/plugins')) {
            $this->filesystem->mkdir($projectDir.'/plugins');
            $this->filesystem->dumpFile($projectDir.'/plugins/plugins.json', '[]');
            $io->writeln('Created plugins/ directory');
        }
    }

    private function updateComposerJson(string $projectDir, SymfonyStyle $io): void
    {
        $composerFile = $projectDir.'/composer.json';
        $skeletonComposerFile = $this->skeletonDir.'/composer.json';

        if (!$this->filesystem->exists($composerFile) || !$this->filesystem->exists($skeletonComposerFile)) {
            return;
        }

        $io->section('Updating composer.json');

        $projectComposerRaw = file_get_contents($composerFile);
        $skeletonComposerRaw = file_get_contents($skeletonComposerFile);
        if (false === $projectComposerRaw || false === $skeletonComposerRaw) {
            $io->error('Failed to read composer.json');

            return;
        }
        $projectComposer = json_decode($projectComposerRaw, true);
        $skeletonComposer = json_decode($skeletonComposerRaw, true);

        if (!is_array($projectComposer) || !is_array($skeletonComposer)) {
            $io->error('Failed to parse composer.json');

            return;
        }

        // Merge require-dev
        if (isset($skeletonComposer['require-dev'])) {
            $projectComposer['require-dev'] = array_merge($projectComposer['require-dev'] ?? [], $skeletonComposer['require-dev']);
        }

        // Merge config
        if (isset($skeletonComposer['config'])) {
            $projectComposer['config'] = array_merge($projectComposer['config'] ?? [], $skeletonComposer['config']);
            if (isset($skeletonComposer['config']['allow-plugins'])) {
                $projectComposer['config']['allow-plugins'] = array_merge(
                    $projectComposer['config']['allow-plugins'] ?? [],
                    $skeletonComposer['config']['allow-plugins']
                );
            }
        }

        // Merge extra
        if (isset($skeletonComposer['extra'])) {
            $projectComposer['extra'] = array_merge($projectComposer['extra'] ?? [], $skeletonComposer['extra']);
        }

        // Merge scripts
        if (isset($skeletonComposer['scripts'])) {
            $projectComposer['scripts'] = array_merge($projectComposer['scripts'] ?? [], $skeletonComposer['scripts']);
        }

        $this->filesystem->dumpFile($composerFile, json_encode($projectComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $io->writeln('Updated composer.json with scripts and dependencies');
    }

    private function patchKernel(string $projectDir, SymfonyStyle $io): void
    {
        $kernelFile = $projectDir.'/src/Kernel.php';
        if (!$this->filesystem->exists($kernelFile)) {
            $io->error('src/Kernel.php not found. Cannot patch.');

            return;
        }

        $io->section('Patching src/Kernel.php');
        $content = file_get_contents($kernelFile);
        if (false === $content) {
            $io->error('Failed to read src/Kernel.php.');

            return;
        }

        // Add declare(strict_types=1); if not present
        if (!str_contains($content, 'declare(strict_types=1);')) {
            $content = str_replace('<?php', "<?php\n\ndeclare(strict_types=1);", $content);
        }

        // Add use statements if not present
        $useStatements = [
            'use Composer\Autoload\ClassLoader;',
            'use JBSNewMedia\VisBundle\Core\JsonKernelPluginLoader;',
            'use JBSNewMedia\VisBundle\Plugin\AbstractBundle;',
            'use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;',
            'use Symfony\Component\DependencyInjection\ContainerBuilder;',
            'use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;',
            'use Symfony\Component\HttpKernel\Bundle\Bundle;',
            'use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;',
        ];

        foreach ($useStatements as $use) {
            if (!str_contains($content, $use)) {
                $content = $this->safePregReplace('/namespace [^;]+;/', "$0\n\n$use", $content, 1);
            }
        }

        $content = str_replace("\n\nuse", "\nuse", $content);
        $content = $this->safePregReplace('/(namespace [^;]+;)\nuse/', "$1\n\nuse", $content);

        // Add property
        if (!str_contains($content, 'private readonly JsonKernelPluginLoader $pluginLoader;')) {
            $content = $this->safePregReplace('/class Kernel extends BaseKernel\s*{/', "$0\n    private readonly JsonKernelPluginLoader \$pluginLoader;", $content);
        }

        // Patch Constructor
        if (str_contains($content, 'public function __construct')) {
            $io->warning('Kernel already has a constructor. Please manualy add ClassLoader and initialize JsonKernelPluginLoader.');
        } else {
            $constructor = "\n    public function __construct(string \$environment, bool \$debug, private readonly ClassLoader \$classLoader)\n    {\n        parent::__construct(\$environment, \$debug);\n        \$this->pluginLoader = new JsonKernelPluginLoader(\$this->classLoader, \$this);\n        \$this->pluginLoader->initializePlugins(\$this->getProjectDir());\n    }";
            $content = $this->safePregReplace('/use MicroKernelTrait;/', "$0\n$constructor", $content);
        }

        // Patch registerBundles
        if (str_contains($content, 'public function registerBundles()')) {
            $io->warning('registerBundles already exists. Please manually integrate plugin loading logic.');
        } else {
            $registerBundles = "\n    public function registerBundles(): iterable\n    {\n        \$bundles = require \$this->getProjectDir().'/config/bundles.php';\n        \$instanciatedBundleNames = [];\n        foreach (\$bundles as \$class => \$envs) {\n            if (isset(\$envs['all']) || isset(\$envs[\$this->environment])) {\n                if (is_subclass_of(\$class, AbstractVisBundle::class)) {\n                    continue;\n                }\n                /**\n                 * @var Bundle \$bundle\n                 */\n                \$bundle = new \$class();\n                \$instanciatedBundleNames[] = \$bundle->getName();\n\n                yield \$bundle;\n            }\n        }\n        yield from \$this->pluginLoader->getBundles(\$this->getKernelParameters(), \$instanciatedBundleNames);\n    }";
            $content = str_replace('use MicroKernelTrait;', "use MicroKernelTrait;\n$registerBundles", $content);
        }

        // Patch build
        if (str_contains($content, 'function build(')) {
            $io->warning('build() already exists. Please manually add $this->pluginLoader->build($container).');
        } else {
            $build = "\n    protected function build(ContainerBuilder \$container): void\n    {\n        parent::build(\$container);\n        \$this->pluginLoader->build(\$container);\n    }";
            $content = $this->safePregReplace('/use MicroKernelTrait;/', "$0\n$build", $content);
        }

        // Patch configureContainer
        if (str_contains($content, 'function configureContainer(')) {
            $io->warning('configureContainer() already exists. Please manually check the configuration logic.');
        } else {
            $configureContainer = "\n    protected function configureContainer(ContainerConfigurator \$container): void\n    {\n        \$container->import('../config/{packages}/*.yaml');\n        \$container->import('../config/{packages}/'.\$this->environment.'/*.yaml');\n\n        if (is_file(\\dirname(__DIR__).'/config/services.yaml')) {\n            \$container->import('../config/{services}.yaml');\n            \$container->import('../config/{services}_'.\$this->environment.'.yaml');\n        } elseif (is_file(\$path = \\dirname(__DIR__).'/config/services.php')) {\n            (require \$path)(\$container->withPath(\$path), \$this);\n        }\n    }";
            $content = $this->safePregReplace('/use MicroKernelTrait;/', "$0\n$configureContainer", $content);
        }

        // Patch configureRoutes and addBundleRoutes
        if (str_contains($content, 'function configureRoutes(')) {
            $io->warning('configureRoutes() already exists. Please manually check the routing logic.');
        } else {
            $configureRoutes = "\n    protected function configureRoutes(RoutingConfigurator \$routes): void\n    {\n        \$routes->import('../config/{routes}/'.\$this->environment.'/*.yaml');\n        \$routes->import('../config/{routes}/*.yaml');\n\n        if (is_file(\\dirname(__DIR__).'/config/routes.yaml')) {\n            \$routes->import('../config/{routes}.yaml');\n        } elseif (is_file(\$path = \\dirname(__DIR__).'/config/routes.php')) {\n            (require \$path)(\$routes->withPath(\$path), \$this);\n        }\n\n        \$this->addBundleRoutes(\$routes);\n    }\n\n    private function addBundleRoutes(RoutingConfigurator \$routes): void\n    {\n        foreach (\$this->getBundles() as \$bundle) {\n            if (\$bundle instanceof AbstractBundle) {\n                if (is_file(\$bundle->getPath().'/config/routes.yaml')) {\n                    \$routes->import(\$bundle->getPath().'/config/{routes}.yaml');\n                } elseif (is_file(\$path = \$bundle->getPath().'/config/routes.php')) {\n                    (require \$path)(\$routes->withPath(\$path), \$this);\n                }\n            }\n            if (\$bundle instanceof AbstractVisBundle) {\n                \$bundle->configureRoutes(\$routes, (string) \$this->environment);\n            }\n        }\n    }";
            $content = $this->safePregReplace('/use MicroKernelTrait;/', "$0\n$configureRoutes", $content);
        }

        file_put_contents($kernelFile, $content);
        $io->writeln('Patched src/Kernel.php');
    }

    private function patchIndexPhp(string $projectDir, SymfonyStyle $io): void
    {
        $indexFile = $projectDir.'/public/index.php';
        if (!$this->filesystem->exists($indexFile)) {
            $io->warning('public/index.php not found. Skipping patch.');

            return;
        }

        $io->section('Patching public/index.php');
        $content = file_get_contents($indexFile);
        if (false === $content) {
            $io->error('Failed to read public/index.php');

            return;
        }

        if (str_contains($content, '$classLoader = require')) {
            $io->warning('public/index.php already seems to load ClassLoader.');

            return;
        }

        $search = 'return function (array $context) {';
        $replace = "return function (array \$context) {\n    \$classLoader = require __DIR__.'/../vendor/autoload.php';";

        if (str_contains($content, $search)) {
            $content = str_replace($search, $replace, $content);
            $content = str_replace('new Kernel($context[\'APP_ENV\'], (bool) $context[\'APP_DEBUG\'])', 'new Kernel($context[\'APP_ENV\'], (bool) $context[\'APP_DEBUG\'], $classLoader)', $content);
            file_put_contents($indexFile, $content);
            $io->writeln('Patched public/index.php');
        } else {
            $io->warning('Could not find return function in public/index.php. Please patch manually.');
        }
    }

    private function patchConsolePhp(string $projectDir, SymfonyStyle $io): void
    {
        $consoleFile = $projectDir.'/bin/console';
        if (!$this->filesystem->exists($consoleFile)) {
            $io->warning('bin/console not found. Skipping patch.');

            return;
        }

        $io->section('Patching bin/console');
        $content = file_get_contents($consoleFile);
        if (false === $content) {
            $io->error('Failed to read bin/console');

            return;
        }

        if (str_contains($content, '$classLoader = require')) {
            $io->warning('bin/console already seems to load ClassLoader.');

            return;
        }

        $search = 'return function (array $context) {';
        $replace = "return function (array \$context) {\n    \$classLoader = require __DIR__.'/../vendor/autoload.php';";

        if (str_contains($content, $search)) {
            $content = str_replace($search, $replace, $content);
            $content = str_replace('new Kernel($context[\'APP_ENV\'], (bool) $context[\'APP_DEBUG\'])', 'new Kernel($context[\'APP_ENV\'], (bool) $context[\'APP_DEBUG\'], $classLoader)', $content);
            file_put_contents($consoleFile, $content);
            $io->writeln('Patched bin/console');
        } else {
            $io->warning('Could not find return function in bin/console. Please patch manually.');
        }
    }

    private function safePregReplace(string $pattern, string $replacement, string $subject, int $limit = -1): string
    {
        $result = preg_replace($pattern, $replacement, $subject, $limit);

        return $result ?? $subject;
    }
}
