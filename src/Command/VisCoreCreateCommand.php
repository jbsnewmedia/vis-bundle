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
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'vis:core:create',
    description: 'Create a new vis installation',
)]
class VisCoreCreateCommand extends Command
{
    protected bool $error = false;

    private readonly string $skeletonDir;

    /**
     * @var array<int, string>
     */
    protected array $errorMessages = [];

    protected string $errorMessage = '';

    public function __construct(
        private readonly KernelInterface $kernel,
        protected Filesystem $filesystem = new Filesystem(),
        ?string $skeletonDir = null,
    ) {
        parent::__construct();
        $this->skeletonDir = $skeletonDir ?? __DIR__.'/../Resources/skeleton/core';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $vis_registration */
        $vis_registration = $io->ask(
            'Do you want to register users in the vis controller? (e.g. <fg=yellow>yes</>)',
            'yes'
        );

        /** @var string $vis_security */
        $vis_security = $io->ask(
            'Do you want update security.yaml? (e.g. <fg=yellow>yes</>)',
            'yes'
        );

        /** @var string $vis_locales */
        $vis_locales = $io->ask(
            'Which languages should be supported? (e.g. <fg=yellow>de,en</>)',
            'de,en'
        );

        /** @var string $vis_darkmode */
        $vis_darkmode = $io->ask(
            'Do you want to enable darkmode? (e.g. <fg=yellow>yes</>)',
            'yes'
        );

        $localesArray = array_filter(array_map(trim(...), explode(',', $vis_locales)));
        $useLocales = count($localesArray) > 1;

        /** @var string $vis_default_locale */
        $vis_default_locale = $io->ask(
            'Which language should be the default language? (e.g. <fg=yellow>en</>)',
            'en'
        );

        $controllerFile = $this->kernel->getProjectDir().'/src/Controller/Vis/MainController.php';
        if (!$this->dumpMainController($controllerFile)) {
            $this->error = true;
        }

        $controllerFile = $this->kernel->getProjectDir().'/src/Controller/Vis/SecurityController.php';
        if (!$this->dumpSecurityController($controllerFile)) {
            $this->error = true;
        }

        if ($useLocales) {
            $controllerFile = $this->kernel->getProjectDir().'/src/Controller/Vis/LocaleController.php';
            if ($this->dumpLocaleController($controllerFile)) {
                $io->info('Created LocaleController: '.$controllerFile);
            } else {
                $this->error = true;
            }
        }

        if ('yes' === $vis_registration) {
            $controllerFile = $this->kernel->getProjectDir().'/src/Controller/Vis/RegistrationController.php';
            if (!$this->dumpRegistrationController($controllerFile)) {
                $this->error = true;
            }
        }

        if ('yes' === $vis_darkmode) {
            $controllerFile = $this->kernel->getProjectDir().'/src/Controller/Vis/DarkmodeController.php';
            if ($this->dumpDarkmodeController($controllerFile)) {
                $io->info('Created DarkmodeController: '.$controllerFile);
            } else {
                $this->error = true;
            }
        }

        if ('yes' === $vis_security) {
            if (!$this->updateSecurityYaml($useLocales)) {
                $this->error = true;
            }
        }

        if (!$this->updateVisYaml($vis_locales, $vis_default_locale)) {
            $this->error = true;
        }

        if ($this->error) {
            $this->errorMessage = implode("\n", $this->errorMessages);
            $io->error($this->errorMessage);

            return Command::FAILURE;
        }

        $io->success('Vis core created successfully!');

        return Command::SUCCESS;
    }

    private function getSkeletonContent(string $skeletonFile): string|false
    {
        return @file_get_contents($skeletonFile);
    }

    protected function dumpMainController(string $controllerFile): bool
    {
        $skeletonFile = $this->skeletonDir.'/MainController.php.skeleton';
        $controllerContent = $this->getSkeletonContent($skeletonFile);

        if (false === $controllerContent) {
            $this->errorMessages[] = 'Skeleton file not found: '.$skeletonFile;

            return false;
        }

        try {
            $this->filesystem->dumpFile($controllerFile, $controllerContent);
        } catch (\Throwable $e) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile.' - '.$e->getMessage();

            return false;
        }

        if (!$this->filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    protected function dumpSecurityController(string $controllerFile): bool
    {
        $skeletonFile = $this->skeletonDir.'/SecurityController.php.skeleton';
        $controllerContent = $this->getSkeletonContent($skeletonFile);

        if (false === $controllerContent) {
            $this->errorMessages[] = 'Skeleton file not found: '.$skeletonFile;

            return false;
        }

        try {
            $this->filesystem->dumpFile($controllerFile, $controllerContent);
        } catch (\Throwable $e) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile.' - '.$e->getMessage();

            return false;
        }

        if (!$this->filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    protected function dumpRegistrationController(string $controllerFile): bool
    {
        $skeletonFile = $this->skeletonDir.'/RegistrationController.php.skeleton';
        $controllerContent = $this->getSkeletonContent($skeletonFile);

        if (false === $controllerContent) {
            $this->errorMessages[] = 'Skeleton file not found: '.$skeletonFile;

            return false;
        }

        try {
            $this->filesystem->dumpFile($controllerFile, $controllerContent);
        } catch (\Throwable $e) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile.' - '.$e->getMessage();

            return false;
        }

        if (!$this->filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    protected function dumpLocaleController(string $controllerFile): bool
    {
        $skeletonFile = $this->skeletonDir.'/LocaleController.php.skeleton';
        $controllerContent = $this->getSkeletonContent($skeletonFile);

        if (false === $controllerContent) {
            $this->errorMessages[] = 'Skeleton file not found: '.$skeletonFile;

            return false;
        }

        try {
            $this->filesystem->dumpFile($controllerFile, $controllerContent);
        } catch (\Throwable $e) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile.' - '.$e->getMessage();

            return false;
        }

        if (!$this->filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    protected function dumpDarkmodeController(string $controllerFile): bool
    {
        $skeletonFile = $this->skeletonDir.'/DarkmodeController.php.skeleton';
        $controllerContent = $this->getSkeletonContent($skeletonFile);

        if (false === $controllerContent) {
            $this->errorMessages[] = 'Skeleton file not found: '.$skeletonFile;

            return false;
        }

        try {
            $this->filesystem->dumpFile($controllerFile, $controllerContent);
        } catch (\Throwable $e) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile.' - '.$e->getMessage();

            return false;
        }

        if (!$this->filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    protected function updateVisYaml(string $locales, string $defaultLocale): bool
    {
        $yamlFile = $this->kernel->getProjectDir().'/config/packages/vis.yaml';

        $localesArray = array_map(trim(...), explode(',', $locales));
        $skeletonFile = $this->skeletonDir.'/vis.yaml.skeleton';
        $content = $this->getSkeletonContent($skeletonFile);

        if (false === $content) {
            $this->errorMessages[] = 'Skeleton file not found: '.$skeletonFile;

            return false;
        }

        $replacements = [
            '{$locales}' => (string) json_encode($localesArray),
            '{$default_locale}' => $defaultLocale,
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        try {
            $this->filesystem->dumpFile($yamlFile, $content);
        } catch (\Exception $e) {
            $this->errorMessages[] = 'Error writing YAML file: '.$e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * @return array<mixed>
     */
    protected function getSecurityPatchData(string $skeletonFile): array
    {
        if (!$this->filesystem->exists($skeletonFile)) {
            return [];
        }

        /** @var array<string, mixed> $patchData */
        $patchData = Yaml::parseFile($skeletonFile);

        if (!isset($patchData['security']) || !is_array($patchData['security'])) {
            return [];
        }

        return (array) $patchData['security'];
    }

    private function updateSecurityYaml(bool $useLocales = true): bool
    {
        $yamlFile = $this->kernel->getProjectDir().'/config/packages/security.yaml';

        if (!$this->filesystem->exists($yamlFile)) {
            $this->errorMessages[] = 'YAML file not found: '.$yamlFile;
            $this->error = true;

            return false;
        }

        $skeletonFile = $this->skeletonDir.'/security.yaml.skeleton';
        $patchSecurity = $this->getSecurityPatchData($skeletonFile);

        try {
            $data = Yaml::parseFile($yamlFile);
        } catch (\Exception $e) {
            $this->errorMessages[] = 'Error parsing YAML file: '.$e->getMessage();
            $this->error = true;

            return false;
        }

        if (!is_array($data)) {
            $this->errorMessages[] = 'Parsed YAML content is not an array.';
            $this->error = true;

            return false;
        }

        if (!isset($data['security']) || !is_array($data['security'])) {
            $data['security'] = [];
        }

        /** @var array<string, mixed> $security */
        $security = $data['security'];
        if (!isset($security['providers']) || !is_array($security['providers'])) {
            $security['providers'] = [];
        }
        if (isset($patchSecurity['providers']) && is_array($patchSecurity['providers']) && isset($patchSecurity['providers']['vis_user_provider'])) {
            $security['providers']['vis_user_provider'] = $patchSecurity['providers']['vis_user_provider'];
        }

        if (!isset($security['firewalls']) || !is_array($security['firewalls'])) {
            $security['firewalls'] = [];
        }

        if (!isset($security['firewalls']['vis'])) {
            $newFirewalls = [];
            $inserted = false;
            foreach ($security['firewalls'] as $key => $value) {
                $newFirewalls[$key] = $value;
                if ('dev' === $key && !$inserted) {
                    $newFirewalls['vis'] = [];
                    $inserted = true;
                }
            }
            if (!$inserted) {
                $newFirewalls['vis'] = [];
            }
            $security['firewalls'] = $newFirewalls;
        }

        if (isset($patchSecurity['firewalls']) && is_array($patchSecurity['firewalls']) && isset($patchSecurity['firewalls']['vis'])) {
            $security['firewalls']['vis'] = $patchSecurity['firewalls']['vis'];
        }

        $accessControls = [];
        if (isset($patchSecurity['access_control']) && is_array($patchSecurity['access_control'])) {
            $accessControls = $patchSecurity['access_control'];
        }
        if ($useLocales) {
            $accessControls[] = ['path' => '^/vis/api', 'roles' => null];
        }

        if (!isset($security['access_control']) || !is_array($security['access_control'])) {
            $security['access_control'] = [];
        }

        $existingPaths = array_column($security['access_control'], 'path');
        $newAccessControls = [];

        foreach ($accessControls as $control) {
            if (!is_array($control)) {
                continue;
            }
            if (!isset($control['path'])) {
                continue;
            }
            if (!in_array($control['path'], $existingPaths, true)) {
                $newAccessControls[] = $control;
            }
        }

        $security['access_control'] = array_merge($newAccessControls, $security['access_control']);

        $data['security'] = $security;

        try {
            $this->filesystem->dumpFile($yamlFile, Yaml::dump($data, 6, 4));
        } catch (\Exception $e) {
            $this->errorMessages[] = 'Error writing YAML file: '.$e->getMessage();

            return false;
        }

        return true;
    }
}
