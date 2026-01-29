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
            'de'
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
        $controllerContent = '
<?php

declare(strict_types=1);

namespace App\Controller\Vis;

use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class MainController extends AbstractController
{
    public function __construct(public Vis $vis)
    {
    }

    #[Route(path: \'/vis\', name: \'vis\', methods: [\'GET\', \'POST\'])]
    #[IsGranted(\'ROLE_USER\')]
    public function index(Request $request, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator): Response
    {
        $tools = $this->vis->getTools();
        if ($request->isMethod(\'POST\')) {
            $selectTool = $request->request->get(\'_tool\');
            $rememberMe = $request->request->get(\'_remember_me\');
            if (\'1\' === $rememberMe) {
                $rememberMe = true;
            } else {
                $rememberMe = false;
            }

            if (!isset($tools[$selectTool])) {
                return new JsonResponse([
                    \'success\' => false,
                    \'message\' => \'\',
                    \'invalid\' => [
                        \'_tool\' => $translator->trans(\'change.error.tool\', domain: \'vis\'),
                    ],
                ]);
            }

            $tool = $tools[$selectTool];
            $target = $urlGenerator->generate(\'vis_\'.$tool->getId().\'_dashboard\');

            $response = new JsonResponse([
                \'success\' => true,
                \'message\' => \'\',
                \'redirect\' => $target,
            ]);

            if ($rememberMe) {
                $cookie = new Cookie(\'vis_tool\', $tool->getId(), time() + 60 * 60 * 24 * 365);
                $response->headers->setCookie($cookie);
            }

            return $response;
        }

        if (1 === count($tools)) {
            $tool = $tools[array_key_first($tools)];

            return $this->redirectToRoute(\'vis_\'.$tool->getId().\'_dashboard\');
        }

        $cookieTool = $request->cookies->get(\'vis_tool\');

        return $this->render(\'@Vis/simple/change.html.twig\', [
            \'tools\' => $tools,
            \'cookieTool\' => $cookieTool,
        ]);
    }
}';

        $filesystem = new Filesystem();
        $filesystem->dumpFile($controllerFile, trim($controllerContent)."\n");

        if (!$filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    private function dumpSecurityController(string $controllerFile): bool
    {
        $controllerContent = '
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: \'/vis/login\', name: \'vis_login\')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render(\'@Vis/simple/login.html.twig\');
    }

    #[Route(path: \'/vis/logout\', name: \'vis_logout\')]
    public function logout(): void
    {
        throw new \LogicException(\'This method can be blank - it will be intercepted by the logout key on your firewall.\');
    }
}';

        $filesystem = new Filesystem();
        $filesystem->dumpFile($controllerFile, trim($controllerContent)."\n");

        if (!$filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    private function dumpRegistrationController(string $controllerFile): bool
    {
        $controllerContent = '
<?php

declare(strict_types=1);

namespace App\Controller\Vis;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\VisBundle\Entity\User;
use JBSNewMedia\VisBundle\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route(path: \'/vis/register\', name: \'vis_register\', priority: 10)]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get(\'plainPassword\')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute(\'vis_login\');
        }

        return $this->render(\'@Vis/simple/register.html.twig\', [
            \'registrationForm\' => $form,
        ]);
    }
}
';

        $filesystem = new Filesystem();
        $filesystem->dumpFile($controllerFile, trim($controllerContent)."\n");

        if (!$filesystem->exists($controllerFile)) {
            $this->errorMessages[] = 'Controller cannot be created: '.$controllerFile;

            return false;
        }

        return true;
    }

    private function dumpLocaleController(string $controllerFile): bool
    {
        $controllerContent = '<?php

declare(strict_types=1);

namespace App\Controller;

use JBSNewMedia\VisBundle\Controller\VisAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends VisAbstractController
{
    #[Route(\'/vis/api/locale/{_locale}\', name: \'vis_api_locale\')]
    public function setLocale(Request $request, string $_locale): JsonResponse
    {
        $request->getSession()->set(\'_locale\', $_locale);

        return new JsonResponse([
            \'locale\' => $_locale,
            \'success\' => true,
        ]);
    }
}
';
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

        $data = [
            'vis' => [
                'locales' => $localesArray,
                'default_locale' => $defaultLocale,
            ],
        ];

        try {
            $filesystem->dumpFile($yamlFile, Yaml::dump($data, 4));
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

        $data['security']['providers']['vis_user_provider'] = [
            'entity' => [
                'class' => \JBSNewMedia\VisBundle\Entity\User::class,
                'property' => 'email',
            ],
        ];

        if (!isset($data['security']['firewalls'])) {
            $data['security']['firewalls'] = [];
        }

        if (!isset($data['security']['firewalls']['vis'])) {
            foreach ($data['security']['firewalls'] as $key => $provider) {
                $array = $data['security']['firewalls'];
                $newArray = [];
                $inserted = false;

                foreach ($array as $key => $value) {
                    $newArray[$key] = $value;
                    if ('dev' === $key && !$inserted) {
                        $newArray['vis'] = [];
                        $inserted = true;
                    }
                }

                $data['security']['firewalls'] = $newArray;
            }
        }

        $data['security']['firewalls']['vis'] = [
            'lazy' => true,
            'provider' => 'vis_user_provider',
            'custom_authenticator' => \JBSNewMedia\VisBundle\Security\VisAuthenticator::class,
            'logout' => [
                'path' => 'vis_logout',
                'target' => 'vis',
            ],
            'remember_me' => [
                'secret' => '%kernel.secret%',
                'lifetime' => 604800,
            ],
        ];

        $accessControls = [
            '^/vis/login' => [
                'path' => '^/vis/login',
                'roles' => null,
            ],
            '^/vis/logout' => [
                'path' => '^/vis/logout',
                'roles' => null,
            ],
            '^/vis/register' => [
                'path' => '^/vis/register',
                'roles' => null,
            ],
            '^/vis' => [
                'path' => '^/vis',
                'roles' => 'ROLE_USER',
            ],
        ];

        if ($useLocales) {
            $accessControls['^/vis/api'] = [
                'path' => '^/vis/api',
                'roles' => null,
            ];
        }
        if (!array_key_exists('security', $data) || !array_key_exists('access_control', $data['security']) || [] === $data['security']['access_control']) {
            $data['security']['access_control'] = [];
            foreach ($accessControls as $accessControl) {
                $data['security']['access_control'][] = [
                    'path' => $accessControl['path'],
                    'roles' => $accessControl['roles'],
                ];
            }
        } else {
            if ((isset($data['security']['access_control'])) && is_array($data['security']['access_control'])) {
                foreach ($data['security']['access_control'] as $accessControl) {
                    if (isset($accessControls[$accessControl['path']])) {
                        unset($accessControls[$accessControl['path']]);
                    }
                }
            }

            $accessControlsNew = [];
            foreach ($accessControls as $accessControl) {
                $accessControlsNew[] = $accessControl;
            }

            if ((isset($data['security']['access_control'])) && is_array($data['security']['access_control'])) {
                foreach ($data['security']['access_control'] as $accessControl) {
                    $accessControlsNew[] = $accessControl;
                }
            }

            $data['security']['access_control'] = $accessControlsNew;
        }

        $yamlContent = Yaml::dump($data, 6, 4);
        $filesystem->dumpFile($yamlFile, $yamlContent);

        return true;
    }
}
