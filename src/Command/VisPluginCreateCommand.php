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

        /** @var string $name */
        $name = $io->ask('Plugin name (e.g. Demo)', null, function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Plugin name cannot be empty');
            }
            if (!preg_match('/^[a-zA-Z0-9]+$/', $answer)) {
                throw new \RuntimeException('Plugin name can only contain a-zA-Z0-9');
            }

            return ucfirst((string) $answer);
        });

        /** @var string $company */
        $company = $io->ask('Company name', 'Company', function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Company name cannot be empty');
            }
            if (!preg_match('/^[a-zA-Z0-9]+$/', $answer)) {
                throw new \RuntimeException('Company name can only contain a-zA-Z0-9');
            }

            return (string) $answer;
        });

        $lcCompany = strtolower($company);
        $lcName = strtolower($name);
        $pluginDirName = sprintf('plugins/%s/vis-%s-plugin', $lcCompany, $lcName);
        $pluginPath = $this->projectDir.'/'.$pluginDirName;

        if ($this->filesystem->exists($pluginPath)) {
            if (!$io->confirm(sprintf('Directory %s already exists. Do you want to delete it?', $pluginDirName), false)) {
                $io->info('Creation cancelled.');

                return Command::SUCCESS;
            }
            $this->filesystem->remove($pluginPath);
            $io->success(sprintf('Deleted existing directory %s', $pluginDirName));
        }

        $addBundle = $io->confirm('Add bundle to config/bundles.php?', true);
        $updateComposer = $io->confirm('Add namespace to composer.json?', true);
        $addRoutes = $io->confirm('Add routes to config/routes.yaml', true);

        $io->section(sprintf('Creating plugin %s in %s', $name, $pluginDirName));

        $this->createPluginStructure($pluginPath, $name, $company);

        if ($addBundle) {
            $this->addBundleToConfig($name, $company);
            $io->success('Added bundle to config/bundles.php');
        }

        if ($updateComposer) {
            $this->updateRootComposer($name, $pluginDirName, $company);
            $io->success('Added namespace to composer.json');
        }

        if ($addRoutes) {
            $this->addRoutesToConfig($name, $pluginDirName);
            $io->success('Added routes to config/routes.yaml');
        }

        $io->success(sprintf('Plugin %s created successfully', $name));

        $io->warning([
            'IMPORTANT: You must run "composer dump" inside your docker container',
            'to update the autoloader for the new namespace!',
        ]);

        return Command::SUCCESS;
    }

    private function createPluginStructure(string $path, string $name, string $company): void
    {
        $bundleName = sprintf('Vis%sPluginBundle', $name);
        $namespace = sprintf('%s\\%s', $company, $bundleName);
        $extensionName = sprintf('Vis%sPluginExtension', $name);
        $lcName = strtolower($name);
        $lcCompany = strtolower($company);
        $ucName = strtoupper($name);

        $directories = [
            '/src/Command',
            '/src/Controller',
            '/src/DependencyInjection',
            '/src/Plugin',
            '/src/Service',
            '/config',
            '/templates/content',
            '/templates/tool',
            '/translations',
        ];

        foreach ($directories as $dir) {
            $this->filesystem->mkdir($path.$dir);
        }

        $replacements = [
            '{$namespace}' => $namespace,
            '{$bundleName}' => $bundleName,
            '{$extensionName}' => $extensionName,
            '{$name}' => $name,
            '{$lcName}' => $lcName,
            '{$lcCompany}' => $lcCompany,
            '{$ucName}' => $ucName,
        ];

        $skeletonDir = __DIR__.'/../Resources/skeleton/plugin';

        $files = [
            'composer.json.skeleton' => '/composer.json',
            'Bundle.php.skeleton' => '/src/'.$bundleName.'.php',
            'Extension.php.skeleton' => '/src/DependencyInjection/'.$extensionName.'.php',
            'Plugin.php.skeleton' => '/src/Plugin/'.$name.'Plugin.php',
            'Controller.php.skeleton' => '/src/Controller/'.$name.'Controller.php',
            'ApiController.php.skeleton' => '/src/Controller/'.$name.'ApiController.php',
            'Service.php.skeleton' => '/src/Service/'.$name.'Service.php',
            'Command.php.skeleton' => '/src/Command/'.$name.'Command.php',
            'dashboard.html.twig.skeleton' => '/templates/tool/dashboard.html.twig',
            'user.html.twig.skeleton' => '/templates/content/user.html.twig',
            'translations.de.yaml.skeleton' => '/translations/vis_'.$lcName.'.de.yaml',
            'translations.en.yaml.skeleton' => '/translations/vis_'.$lcName.'.en.yaml',
            'services.yaml.skeleton' => '/config/services.yaml',
        ];

        foreach ($files as $skeletonFile => $targetFile) {
            $content = file_get_contents($skeletonDir.'/'.$skeletonFile);
            $content = str_replace(array_keys($replacements), array_values($replacements), $content);
            $this->filesystem->dumpFile($path.$targetFile, $content);
        }
    }

    private function addBundleToConfig(string $name, string $company): void
    {
        $bundlesFile = $this->projectDir.'/config/bundles.php';
        if (!$this->filesystem->exists($bundlesFile)) {
            return;
        }

        $bundleClass = sprintf('%s\\Vis%sPluginBundle\\Vis%sPluginBundle', $company, $name, $name);
        $content = file_get_contents($bundlesFile);
        if (false === $content) {
            return;
        }

        if (str_contains($content, $bundleClass)) {
            return;
        }

        $newItem = sprintf("    %s::class => ['all' => true],\n];", $bundleClass);
        $content = str_replace('];', $newItem, $content);
        file_put_contents($bundlesFile, $content);
    }

    private function updateRootComposer(string $name, string $pluginDirName, string $company): void
    {
        $composerFile = $this->projectDir.'/composer.json';
        if (!$this->filesystem->exists($composerFile)) {
            return;
        }

        $content = file_get_contents($composerFile);
        if (false === $content) {
            return;
        }
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return;
        }

        $namespace = sprintf('%s\\Vis%sPluginBundle\\', $company, $name);
        $path = $pluginDirName.'/src/';

        if (!isset($data['autoload'])) {
            $data['autoload'] = [];
        }
        if (!isset($data['autoload']['psr-4'])) {
            $data['autoload']['psr-4'] = [];
        }
        $data['autoload']['psr-4'][$namespace] = $path;

        file_put_contents($composerFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    private function addRoutesToConfig(string $name, string $pluginDirName): void
    {
        $routesFile = $this->projectDir.'/config/routes.yaml';
        if (!$this->filesystem->exists($routesFile)) {
            return;
        }

        $lcName = strtolower($name);
        $routeName = sprintf('vis_%s_plugin', $lcName);
        $content = file_get_contents($routesFile);
        if (false === $content) {
            return;
        }

        if (str_contains($content, $routeName.':')) {
            return;
        }

        $newRoute = sprintf(
            "\n%s:\n    resource: ../%s/src/Controller/\n    type: attribute\n",
            $routeName,
            $pluginDirName
        );

        file_put_contents($routesFile, $content.$newRoute);
    }
}
