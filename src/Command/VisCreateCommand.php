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
    name: 'vis:create',
    description: 'Create a new vis installation',
)]
class VisCreateCommand extends Command
{
    protected bool $error = false;

    protected string $errorMessage = '';

    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $vis_controller = $io->ask(
            'The name for the controllers (e.g. <fg=yellow>Vis</>)',
            'Vis'
        );

        $vis_route = $io->ask(
            'The name of the vis route (e.g. <fg=yellow>vis</>)',
            'vis'
        );

        $vis_registration = $io->ask(
            'Do you want to register users in the vis controller? (e.g. <fg=yellow>yes</>)',
            'yes'
        );

        $vis_security = $io->ask(
            'Do you want update security.yaml? (e.g. <fg=yellow>yes</>)',
            'yes'
        );

        $filesystem = new Filesystem();
        $controllerFile = $this->kernel->getProjectDir().'/src/Controller/'.$vis_controller.'/MainController.php';

        if ($filesystem->exists($controllerFile)) {
            $this->errorMessage = 'Controller file already exists: '.$controllerFile;
            $io->error($this->errorMessage);

            return Command::FAILURE;
        }

        $controllerContent = [];
        $controllerContent[] = '<?php';
        $controllerContent[] = '';
        $controllerContent[] = 'namespace App\Controller\\'.$vis_controller.';';
        $controllerContent[] = '';
        $controllerContent[] = 'use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;';
        $controllerContent[] = 'use Symfony\Component\HttpFoundation\Response;';
        $controllerContent[] = 'use Symfony\Component\Routing\Annotation\Route;';
        $controllerContent[] = '';
        $controllerContent[] = 'class MainController extends AbstractController';
        $controllerContent[] = '{';
        $controllerContent[] = '    #[Route("/'.$vis_route.'", name="'.$vis_route.'_index")]';
        $controllerContent[] = '    public function index(): Response';
        $controllerContent[] = '    {';
        $controllerContent[] = '        return new Response(';
        $controllerContent[] = '            \'<html><body>Hello '.$vis_controller.'!</body></html>\'';
        $controllerContent[] = '        );';
        $controllerContent[] = '    }';
        $controllerContent[] = '}';

        $controllerContent = implode("\n", $controllerContent);
        $filesystem->dumpFile($controllerFile, $controllerContent);

        if ('yes' === $vis_security) {
            $this->updateSecurityYaml($vis_controller, $vis_route);
        }

        $io->success('Vis controller and security settings have been created successfully.');

        return Command::SUCCESS;
    }

    private function updateSecurityYaml(string $vis_controller, string $vis_route): bool
    {
        $yamlFile = $this->kernel->getProjectDir().'/config/packages/security.yaml';
        $filesystem = new Filesystem();

        if (!$filesystem->exists($yamlFile)) {
            $this->errorMessage = 'YAML file not found: '.$yamlFile;

            return false;
        }

        try {
            $data = Yaml::parseFile($yamlFile);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error parsing YAML file: '.$e->getMessage();

            return false;
        }

        $data['security']['providers']['vis_user_provider'] = [
            'entity' => [
                'class' => \JBSNewMedia\VisBundle\Entity\User::class,
                'property' => 'email',
            ],
        ];

        $yamlContent = Yaml::dump($data, 8, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $filesystem->dumpFile($yamlFile, $yamlContent);

        return true;
    }
}
