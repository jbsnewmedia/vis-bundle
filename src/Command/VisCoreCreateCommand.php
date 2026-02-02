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

    /**
     * @var array<int, string>
     */
    protected array $errorMessages = [];

    protected string $errorMessage = '';

    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $vis_registration = $io->ask(
            'Do you want to register users in the vis controller? (e.g. <fg=yellow>yes</>)',
            'yes'
        );

        $vis_security = $io->ask(
            'Do you want update security.yaml? (e.g. <fg=yellow>yes</>)',
            'yes'
        );

        $vis_locales = $io->ask(
            'Which languages should be supported? (e.g. <fg=yellow>de,en</>)',
            'de,en'
        );

        $localesArray = array_filter(array_map('trim', explode(',', $vis_locales)));
        $useLocales = count($localesArray) > 1;

        $vis_default_locale = $io->ask(
            'Which language should be the default language? (e.g. <fg=yellow>en</>)',
            'en'
        );

        $controllerFile = $this->kernel->getProjectDir().'/src/Controller/Vis/MainController.php';
        $this->dumpMainController($controllerFile);

        $controllerFile = $this->kernel->getProjectDir().'/src/Controller/SecurityController.php';
        $this->dumpSecurityController($controllerFile);

        if ($useLocales) {
            $controllerFile = $this->kernel->getProjectDir().'/src/Controller/LocaleController.php';
            if ($this->dumpLocaleController($controllerFile)) {
                $io->info('Created LocaleController: '.$controllerFile);
            }
        }

        if ('yes' === $vis_registration) {
            $controllerFile = $this->kernel->getProjectDir().'/src/Controller/Vis/RegistrationController.php';
            $this->dumpRegistrationController($controllerFile);
        }

        if ('yes' === $vis_security) {
            $this->updateSecurityYaml($useLocales);
        }

        $this->updateVisYaml($vis_locales, $vis_default_locale);

        if ($this->error) {
            $this->errorMessage = implode("\n", $this->errorMessages);
            $io->error($this->errorMessage);

            return Command::FAILURE;
        }

        $io->success('Vis core created successfully!');

        return Command::SUCCESS;
    }

    private function dumpMainController(string $controllerFile): bool
    {
        $skeletonFile = __DIR__.'/../Resources/skeleton/core/MainController.php.skeleton';
        $controllerContent = file_get_contents($skeletonFile);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($controllerFile, $controllerContent);

        if (!$filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    private function dumpSecurityController(string $controllerFile): bool
    {
        $skeletonFile = __DIR__.'/../Resources/skeleton/core/SecurityController.php.skeleton';
        $controllerContent = file_get_contents($skeletonFile);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($controllerFile, $controllerContent);

        if (!$filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    private function dumpRegistrationController(string $controllerFile): bool
    {
        $skeletonFile = __DIR__.'/../Resources/skeleton/core/RegistrationController.php.skeleton';
        $controllerContent = file_get_contents($skeletonFile);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($controllerFile, $controllerContent);

        if (!$filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    private function dumpLocaleController(string $controllerFile): bool
    {
        $skeletonFile = __DIR__.'/../Resources/skeleton/core/LocaleController.php.skeleton';
        $controllerContent = file_get_contents($skeletonFile);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($controllerFile, $controllerContent);

        if (!$filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    private function updateVisYaml(string $locales, string $defaultLocale): bool
    {
        $yamlFile = $this->kernel->getProjectDir().'/config/packages/vis.yaml';
        $filesystem = new Filesystem();

        $localesArray = array_map('trim', explode(',', $locales));
        $skeletonFile = __DIR__.'/../Resources/skeleton/core/vis.yaml.skeleton';
        $content = file_get_contents($skeletonFile);

        $replacements = [
            '{$locales}' => json_encode($localesArray),
            '{$default_locale}' => $defaultLocale,
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        try {
            $filesystem->dumpFile($yamlFile, $content);
        } catch (\Exception $e) {
            $this->errorMessages[] = 'Error writing YAML file: '.$e->getMessage();

            return false;
        }

        return true;
    }

    private function updateSecurityYaml(bool $useLocales = true): bool
    {
        $yamlFile = $this->kernel->getProjectDir().'/config/packages/security.yaml';
        $filesystem = new Filesystem();

        if (!$filesystem->exists($yamlFile)) {
            $this->errorMessages[] = 'YAML file not found: '.$yamlFile;

            return false;
        }

        $skeletonFile = __DIR__.'/../Resources/skeleton/core/security.yaml.skeleton';
        $patchData = Yaml::parseFile($skeletonFile);

        try {
            $data = Yaml::parseFile($yamlFile);
        } catch (\Exception $e) {
            $this->errorMessages[] = 'Error parsing YAML file: '.$e->getMessage();

            return false;
        }

        if (!is_array($data)) {
            $this->errorMessages[] = 'Parsed YAML content is not an array.';

            return false;
        }

        if (!isset($data['security']) || !is_array($data['security'])) {
            $data['security'] = [];
        }

        $data['security']['providers']['vis_user_provider'] = $patchData['security']['providers']['vis_user_provider'];

        if (!isset($data['security']['firewalls'])) {
            $data['security']['firewalls'] = [];
        }

        if (!isset($data['security']['firewalls']['vis'])) {
            $newFirewalls = [];
            $inserted = false;
            foreach ($data['security']['firewalls'] as $key => $value) {
                $newFirewalls[$key] = $value;
                if ('dev' === $key && !$inserted) {
                    $newFirewalls['vis'] = [];
                    $inserted = true;
                }
            }
            if (!$inserted) {
                $newFirewalls['vis'] = [];
            }
            $data['security']['firewalls'] = $newFirewalls;
        }

        $data['security']['firewalls']['vis'] = $patchData['security']['firewalls']['vis'];

        $accessControls = $patchData['security']['access_control'];
        if ($useLocales) {
            $accessControls[] = ['path' => '^/vis/api', 'roles' => null];
        }

        if (!isset($data['security']['access_control']) || !is_array($data['security']['access_control'])) {
            $data['security']['access_control'] = [];
        }

        $existingPaths = array_column($data['security']['access_control'], 'path');
        $newAccessControls = [];

        foreach ($accessControls as $control) {
            if (!in_array($control['path'], $existingPaths)) {
                $newAccessControls[] = $control;
            }
        }

        $data['security']['access_control'] = array_merge($newAccessControls, $data['security']['access_control']);

        try {
            $filesystem->dumpFile($yamlFile, Yaml::dump($data, 6, 4));
        } catch (\Exception $e) {
            $this->errorMessages[] = 'Error writing YAML file: '.$e->getMessage();

            return false;
        }

        return true;
    }
}
