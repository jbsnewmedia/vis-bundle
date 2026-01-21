<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'vis:plugin:create',
    description: 'Create a new vis plugin',
)]
class VisPluginCreateCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $io->ask('Plugin name (e.g. Demo)', null, function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Plugin name cannot be empty');
            }
            return ucfirst($answer);
        });

        $company = $io->ask('Company name', 'JBSNewMedia', function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Company name cannot be empty');
            }
            return $answer;
        });

        $addBundle = $io->confirm('Add bundle to config/bundles.php?', true);
        $updateComposer = $io->confirm('Update root composer.json autoload?', true);

        $lcCompany = strtolower($company);
        $lcName = strtolower($name);
        $pluginDirName = sprintf('plugins/%s/vis-%s-plugin', $lcCompany, $lcName);
        $pluginPath = $this->projectDir . '/' . $pluginDirName;

        if ($this->filesystem->exists($pluginPath)) {
            $io->error(sprintf('Directory %s already exists', $pluginDirName));
            return Command::FAILURE;
        }

        $io->section(sprintf('Creating plugin %s in %s', $name, $pluginDirName));

        $this->createPluginStructure($pluginPath, $name, $company);

        if ($addBundle) {
            $this->addBundleToConfig($name, $company);
            $io->success('Added bundle to config/bundles.php');
        }

        if ($updateComposer) {
            $this->updateRootComposer($name, $pluginDirName, $company);
            $io->success('Updated root composer.json');
        }

        $io->success(sprintf('Plugin %s created successfully', $name));

        return Command::SUCCESS;
    }

    private function createPluginStructure(string $path, string $name, string $company): void
    {
        $bundleName = sprintf('Vis%sPluginBundle', $name);
        $namespace = sprintf('%s\\%s', $company, $bundleName);
        $extensionName = sprintf('Vis%sPluginExtension', $name);
        $lcName = strtolower($name);
        $lcCompany = strtolower($company);

        // Directories
        $directories = [
            '/src/Command',
            '/src/Controller',
            '/src/DependencyInjection',
            '/src/Entity',
            '/src/Entity/Enum',
            '/src/Factory',
            '/src/Plugin',
            '/src/Repository',
            '/src/Repository/Trait',
            '/src/Service',
            '/src/Story',
            '/config',
            '/translations',
            '/tests',
        ];

        foreach ($directories as $dir) {
            $this->filesystem->mkdir($path . $dir);
        }

        // composer.json
        $composerJson = [
            'name' => sprintf('%s/vis-%s-plugin', $lcCompany, $lcName),
            'type' => 'symfony-vis-plugin',
            'license' => 'MIT',
            'description' => sprintf('VIS %s Plugin', $name),
            'authors' => [
                [
                    'name' => 'Juergen Schwind',
                    'email' => 'info@juergen.schwind.de',
                ],
            ],
            'autoload' => [
                'psr-4' => [
                    $namespace . '\\' => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $namespace . '\\Tests\\' => 'tests/',
                ],
            ],
        ];
        $this->filesystem->dumpFile($path . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        // Bundle class
        $bundleContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace {$namespace};

use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use {$namespace}\DependencyInjection\{$extensionName};
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class {$bundleName} extends AbstractVisBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new {$extensionName}();
        }

        if (false === $this->extension) {
            return null;
        }

        return $this->extension;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
PHP;
        $bundleContent = str_replace(
            ['{$namespace}', '{$extensionName}', '{$bundleName}'],
            [$namespace, $extensionName, $bundleName],
            $bundleContent
        );
        $this->filesystem->dumpFile($path . '/src/' . $bundleName . '.php', $bundleContent);

        // Extension class
        $extensionContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace {$namespace}\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class {$extensionName} extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }
}
PHP;
        $extensionContent = str_replace(
            ['{$namespace}', '{$extensionName}'],
            [$namespace, $extensionName],
            $extensionContent
        );
        $this->filesystem->dumpFile($path . '/src/DependencyInjection/' . $extensionName . '.php', $extensionContent);

        // Controller
        $controllerContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace {$namespace}\Controller;

use JBSNewMedia\VisBundle\Controller\VisAbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{$lcName}', name: 'vis_{$lcName}_')]
class {$name}Controller extends VisAbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('@{$bundleName}/index.html.twig', [
            'controller_name' => '{$name}Controller',
        ]);
    }
}
PHP;
        $controllerContent = str_replace(
            ['{$namespace}', '{$name}', '{$lcName}', '{$bundleName}'],
            [$namespace, $name, $lcName, $bundleName],
            $controllerContent
        );
        $this->filesystem->dumpFile($path . '/src/Controller/' . $name . 'Controller.php', $controllerContent);

        // API Controller
        $apiControllerContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace {$namespace}\Controller;

use JBSNewMedia\VisBundle\Controller\VisAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/{$lcName}', name: 'vis_api_{$lcName}_')]
class {$name}ApiController extends VisAbstractController
{
    #[Route('', name: 'index')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new {$name} API controller!',
            'path' => 'src/Controller/{$name}ApiController.php',
        ]);
    }
}
PHP;
        $apiControllerContent = str_replace(
            ['{$namespace}', '{$name}', '{$lcName}'],
            [$namespace, $name, $lcName],
            $apiControllerContent
        );
        $this->filesystem->dumpFile($path . '/src/Controller/' . $name . 'ApiController.php', $apiControllerContent);

        // Service
        $serviceContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace {$namespace}\Service;

class TestService
{
    public function getTestMessage(): string
    {
        return 'This is a test message from the TestService of {$name} plugin.';
    }
}
PHP;
        $serviceContent = str_replace(
            ['{$namespace}', '{$name}'],
            [$namespace, $name],
            $serviceContent
        );
        $this->filesystem->dumpFile($path . '/src/Service/TestService.php', $serviceContent);

        // Command
        $commandContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace {$namespace}\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'vis:{$lcName}:test',
    description: 'Test command for {$name} plugin',
)]
class TestCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Test command for {$name} plugin executed successfully.');

        return Command::SUCCESS;
    }
}
PHP;
        $commandContent = str_replace(
            ['{$namespace}', '{$name}', '{$lcName}'],
            [$namespace, $name, $lcName],
            $commandContent
        );
        $this->filesystem->dumpFile($path . '/src/Command/TestCommand.php', $commandContent);

        // Translations
        $translationsDe = <<<YAML
main.title: "{$name}"
navigation:
  header_main: "Hauptbereich"
  dashboard: "Übersicht"
YAML;
        $this->filesystem->dumpFile($path . '/translations/vis_' . $lcName . '.de.yaml', $translationsDe);
        $this->filesystem->dumpFile($path . '/translations/vis_' . $lcName . '.en.yaml', str_replace('Übersicht', 'Overview', $translationsDe));

        // services.yaml
        $servicesYaml = <<<'YAML'
services:
    _defaults:
        autowire: true
        autoconfigure: true

    {$namespace}\Service\:
        resource: '../src/Service/'

    {$namespace}\:
        resource: '../src/'
        exclude:
            - '../../DependencyInjection/'
            - '../../Entity/'
            - '../../Kernel.php'
        tags: [ 'controller.service_arguments' ]

    {$namespace}\Controller\{$name}Controller:
        arguments:
            $vis: '@JBSNewMedia\VisBundle\Service\Vis'
        tags: ['controller.service_arguments']
YAML;
        $servicesYaml = str_replace(['{$namespace}', '{$name}'], [$namespace, $name], $servicesYaml);
        $this->filesystem->dumpFile($path . '/config/services.yaml', $servicesYaml);
    }

    private function addBundleToConfig(string $name, string $company): void
    {
        $bundlesFile = $this->projectDir . '/config/bundles.php';
        if (!$this->filesystem->exists($bundlesFile)) {
            return;
        }

        $bundleClass = sprintf('%s\\Vis%sPluginBundle\\Vis%sPluginBundle', $company, $name, $name);
        $content = file_get_contents($bundlesFile);

        if (str_contains($content, $bundleClass)) {
            return;
        }

        $newItem = sprintf("    %s::class => ['all' => true],\n];", $bundleClass);
        $content = str_replace('];', $newItem, $content);
        file_put_contents($bundlesFile, $content);
    }

    private function updateRootComposer(string $name, string $pluginDirName, string $company): void
    {
        $composerFile = $this->projectDir . '/composer.json';
        if (!$this->filesystem->exists($composerFile)) {
            return;
        }

        $data = json_decode(file_get_contents($composerFile), true);
        $namespace = sprintf('%s\\Vis%sPluginBundle\\', $company, $name);
        $path = $pluginDirName . '/src/';

        $data['autoload']['psr-4'][$namespace] = $path;

        file_put_contents($composerFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}
