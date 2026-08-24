<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Command;

use JBSNewMedia\BootstrapBundle\Service\ScssCompilerFactory;
use ScssPhp\ScssPhp\OutputStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'vis:themes:compile',
    description: 'Compile VIS theme SCSS into native CSS (Bootstrap variables baked in). Writes theme.css and theme.min.css per theme.'
)]
class ThemeCompileCommand extends Command
{
    private readonly string $projectDir;

    private readonly string $bundleDir;

    public function __construct(
        KernelInterface $kernel,
        private readonly ?ScssCompilerFactory $compilerFactory = null,
    ) {
        parent::__construct();
        $this->projectDir = $kernel->getProjectDir();
        $this->bundleDir = dirname(__DIR__, 2);
    }

    protected function configure(): void
    {
        $this
            ->addOption('theme', 't', InputOption::VALUE_REQUIRED, 'Compile only this theme (e.g. pina, modern). Default: all themes with SCSS sources');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $factory = $this->compilerFactory ?? new ScssCompilerFactory();
        if (!$factory->isAvailable()) {
            $io->error('Required class ScssPhp\ScssPhp\Compiler not found. Make sure "scssphp/scssphp" is installed (provided by jbsnewmedia/bootstrap-bundle).');

            return Command::FAILURE;
        }

        $bootstrapScss = $this->findBootstrapScss();
        if (null === $bootstrapScss) {
            $io->error('Bootstrap SCSS sources not found. Make sure "twbs/bootstrap" is installed via composer.');

            return Command::FAILURE;
        }

        $only = is_scalar($input->getOption('theme')) ? trim((string) $input->getOption('theme')) : '';
        $themesDir = $this->bundleDir.'/assets/themes';

        if ('' !== $only && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $only)) {
            $io->error(sprintf('Invalid theme name "%s".', $only));

            return Command::FAILURE;
        }

        $themes = [];
        $entries = is_dir($themesDir) ? (glob($themesDir.'/*/scss/theme.scss') ?: []) : [];
        foreach ($entries as $scssFile) {
            $theme = basename(dirname(dirname($scssFile)));
            if ('' === $only || $theme === $only) {
                $themes[$theme] = $scssFile;
            }
        }

        if ('' !== $only && !isset($themes[$only])) {
            if (is_dir($themesDir.'/'.$only) && !is_file($themesDir.'/'.$only.'/scss/theme.scss')) {
                $io->error(sprintf('Theme "%s" has no SCSS source (expected assets/themes/%s/scss/theme.scss).', $only, $only));
            } else {
                $io->error(sprintf('Theme "%s" not found in "%s".', $only, $themesDir));
            }

            return Command::FAILURE;
        }

        if ([] === $themes) {
            $io->warning('No theme SCSS sources found.');

            return Command::SUCCESS;
        }

        $io->title('VIS themes');
        $io->text(sprintf('Bootstrap SCSS: %s', $bootstrapScss));

        $failed = false;
        foreach ($themes as $theme => $scssFile) {
            $io->section(sprintf('Theme "%s"', $theme));

            $scss = file_get_contents($scssFile);
            if (false === $scss) {
                $io->error(sprintf('Failed to read "%s".', $scssFile));
                $failed = true;

                continue;
            }

            $cssDir = dirname(dirname($scssFile)).'/css';
            if (!is_dir($cssDir) && !@mkdir($cssDir, 0777, true) && !is_dir($cssDir)) {
                $io->error(sprintf('Failed to create directory "%s".', $cssDir));
                $failed = true;

                continue;
            }

            foreach ([['theme.css', OutputStyle::EXPANDED], ['theme.min.css', OutputStyle::COMPRESSED]] as [$fileName, $outputStyle]) {
                $compiler = $factory->create();
                $compiler->setImportPaths([$bootstrapScss, dirname($scssFile)]);
                $compiler->setOutputStyle($outputStyle);

                try {
                    $css = $compiler->compileString($scss, $scssFile)->getCss();
                    if (false === file_put_contents($cssDir.'/'.$fileName, $css)) {
                        throw new \RuntimeException(sprintf('Failed to write "%s".', $cssDir.'/'.$fileName));
                    }
                    $io->writeln(sprintf(' <info>[OK]</info> %s (%s)', $fileName, $this->formatBytes(strlen($css))));
                } catch (\Throwable $e) {
                    $io->error(sprintf('Compiling "%s" failed: %s', $fileName, $e->getMessage()));
                    $failed = true;
                }
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    private function findBootstrapScss(): ?string
    {
        $candidates = [];

        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $installPath = \Composer\InstalledVersions::getInstallPath('twbs/bootstrap');
                if (is_string($installPath) && '' !== $installPath) {
                    $candidates[] = $installPath.'/scss';
                }
            } catch (\OutOfBoundsException) {
            }
        }

        $candidates[] = $this->projectDir.'/vendor/twbs/bootstrap/scss';
        $candidates[] = $this->bundleDir.'/vendor/twbs/bootstrap/scss';

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        return round($bytes / 1024, 1).' KB';
    }
}
