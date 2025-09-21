<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'vis:plugin:create', description: 'Create a VIS bundle plugin skeleton in plugins/COMPANY/vis-NAME-plugin')]
class VisPluginCreateCommand extends Command
{
    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('company', InputArgument::REQUIRED, 'Company/vendor name (e.g. jbsnewmedia)')
            ->addArgument('name', InputArgument::REQUIRED, 'Plugin short name (e.g. basic)')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Human readable label', '')
            ->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Description', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $company = strtolower((string) $input->getArgument('company'));
        $name = strtolower((string) $input->getArgument('name'));
        $label = (string) $input->getOption('label'); // currently unused but kept for compatibility
        $description = (string) $input->getOption('description'); // currently unused but kept for compatibility

        if (!preg_match('/^[a-z0-9\\-]+$/', $company)) {
            $output->writeln('<error>Invalid company. Use lowercase letters, numbers and dashes only.</error>');
            return Command::INVALID;
        }
        if (!preg_match('/^[a-z0-9\\-]+$/', $name)) {
            $output->writeln('<error>Invalid name. Use lowercase letters, numbers and dashes only.</error>');
            return Command::INVALID;
        }

        $projectDir = $this->kernel->getProjectDir();
        $pluginDirName = sprintf('vis-%s-plugin', $name);
        $relativePath = sprintf('plugins/%s/%s', $company, $pluginDirName);
        $targetPath = $projectDir . '/' . $relativePath;

        $filesystem = new Filesystem();
        if ($filesystem->exists($targetPath)) {
            $output->writeln(sprintf('<error>Target directory already exists: %s</error>', $relativePath));
            return Command::FAILURE;
        }

        // Derive Namespace and Class names from input
        $studlyName = self::toStudlyCaps($name);
        $studlyCompany = self::toStudlyCaps($company, keepDashes: false);
        $rootNamespace = sprintf('%s\\Vis%sPluginBundle', $studlyCompany, $studlyName);
        $extensionClass = sprintf('Vis%sPluginExtension', $studlyName);
        $bundleClass = sprintf('Vis%sPluginBundle', $studlyName);

        // Create directories
        $filesystem->mkdir([
            $targetPath,
            $targetPath . '/config',
            $targetPath . '/src/DependencyInjection',
        ], 0775);

        // .editorconfig
        $filesystem->dumpFile($targetPath . '/.editorconfig', <<<TXT
# This is the top-most .editorconfig file; do not search in parent directories.
root = true

# All files.
[*]
end_of_line = lf
indent_style = space
indent_size = 4
charset = utf-8
trim_trailing_whitespace = true
insert_final_newline = true

[*.md]
trim_trailing_whitespace = false

[*.json]
indent_size = 2

[composer.json]
indent_size = 4

[config-schema.json]
indent_size = 4
TXT);

        // .gitattributes
        $filesystem->dumpFile($targetPath . '/.gitattributes', <<<TXT
*.css text eol=lf
*.htaccess text eol=lf
*.htm text eol=lf
*.html text eol=lf
*.js text eol=lf
*.json text eol=lf
*.map text eol=lf
*.md text eol=lf
*.php text eol=lf
*.profile text eol=lf
*.script text eol=lf
*.sh text eol=lf
*.svg text eol=lf
*.txt text eol=lf
*.xml text eol=lf
*.yml text eol=lf
/vendor-bin/**/composer.lock binary
TXT);

        // .gitignore
        $filesystem->dumpFile($targetPath . '/.gitignore', <<<TXT
/.idea/
/vendor/
/vendor-bin/**/vendor/
/.php-cs-fixer.cache
/phpstan.neon
/ai.txt
TXT);

        // composer.json (derived from input)
        $packageName = $company . '/' . $pluginDirName;
        $filesystem->dumpFile($targetPath . '/composer.json', <<<JSON
{
  "name": "$packageName",
  "type": "symfony-vis-plugin",
  "license": "MIT",
  "description": "VIS Plugin",
  "authors": [
    {
      "name": "First Last",
      "email": "Email"
    }
  ],
  "config": {
    "allow-plugins": {
      "bamarni/composer-bin-plugin": false
    }
  }
}
JSON);

        // config/services.yaml
        $filesystem->dumpFile($targetPath . '/config/services.yaml', <<<YAML
services:
    _defaults:
        autowire: true
        autoconfigure: true

#    {$rootNamespace}\Service\:
#        resource: '../src/Service/'

#    {$rootNamespace}\:
#        resource: '../src/'
#        exclude:
#            - '../../DependencyInjection/'
#            - '../../Entity/'
#            - '../../Kernel.php'
#        tags: [ 'controller.service_arguments' ]
YAML);

        // src/DependencyInjection/{ExtensionClass}.php
        $extensionTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace %s\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class %s extends Extension implements PrependExtensionInterface
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
        $filesystem->dumpFile(
            $targetPath . '/src/DependencyInjection/' . $extensionClass . '.php',
            sprintf($extensionTemplate, $rootNamespace, $extensionClass)
        );

        // src/{BundleClass}.php
        $bundleTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace %s;

use JBSNewMedia\VisBundle\Plugin\AbstractVisBundle;
use %s\DependencyInjection\%s;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class %s extends AbstractVisBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new %s();
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
        $filesystem->dumpFile(
            $targetPath . '/src/' . $bundleClass . '.php',
            sprintf($bundleTemplate, $rootNamespace, $rootNamespace, $extensionClass, $bundleClass, $extensionClass)
        );

        $output->writeln('<info>Plugin (bundle skeleton) created successfully.</info>');
        $output->writeln(' Location: ' . $relativePath);
        $output->writeln(' Files created: .editorconfig, .gitattributes, .gitignore, composer.json, config/services.yaml, src/DependencyInjection/' . $extensionClass . '.php, src/' . $bundleClass . '.php');

        return Command::SUCCESS;
    }

    private static function toStudlyCaps(string $value, bool $keepDashes = true): string
    {
        $value = $keepDashes ? $value : str_replace('-', ' ', $value);
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }
}
