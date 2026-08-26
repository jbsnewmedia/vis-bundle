<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Command;

use JBSNewMedia\VisBundle\Core\PluginService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'vis:plugin:enable',
    description: 'Enable a vis plugin',
)]
class VisPluginEnableCommand extends Command
{
    private readonly PluginService $pluginService;

    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
        $this->pluginService = new PluginService($this->kernel);
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Plugin directory name (e.g. vis-demo-plugin)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        if (!is_string($name)) {
            $io->error('Plugin name must be a string.');

            return Command::FAILURE;
        }

        $relativePath = $this->resolvePluginRelativePath($name);
        if (null === $relativePath) {
            $io->error(sprintf('Plugin directory not found: %s', $this->kernel->getProjectDir().'/plugins/'.$name));

            return Command::FAILURE;
        }

        if (!$this->pluginService->enablePlugin($relativePath)) {
            $io->error(sprintf(
                'Plugin "%s" cannot be enabled. Check that plugins/%s/composer.json exists and contains "extra.amicron-platform-plugin-class".',
                $relativePath,
                $relativePath
            ));

            return Command::FAILURE;
        }

        $io->success(sprintf('Plugin "%s" enabled successfully.', $relativePath));

        return Command::SUCCESS;
    }

    protected function resolvePluginRelativePath(string $name): ?string
    {
        $pluginsDir = $this->kernel->getProjectDir().'/plugins';

        if (is_dir($pluginsDir.'/'.$name)) {
            return $name;
        }

        $candidates = glob($pluginsDir.'/*/'.$name, GLOB_ONLYDIR);
        if (false !== $candidates && [] !== $candidates) {
            return str_replace($pluginsDir.'/', '', (string) $candidates[0]);
        }

        return null;
    }
}
