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
            if (!preg_match('/^[a-zA-Z0-9]+$/', $answer)) {
                throw new \RuntimeException('Plugin name can only contain a-zA-Z0-9');
            }
            return ucfirst($answer);
        });

        $company = $io->ask('Company name', 'Company', function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Company name cannot be empty');
            }
            if (!preg_match('/^[a-zA-Z0-9]+$/', $answer)) {
                throw new \RuntimeException('Company name can only contain a-zA-Z0-9');
            }
            return $answer;
        });

        $lcCompany = strtolower($company);
        $lcName = strtolower($name);
        $pluginDirName = sprintf('plugins/%s/vis-%s-plugin', $lcCompany, $lcName);
        $pluginPath = $this->projectDir . '/' . $pluginDirName;

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

        // Directories
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
            $this->filesystem->mkdir($path . $dir);
        }

        // composer.json
        $composerJson = [
            'name' => sprintf('%s/vis-%s-plugin', $lcCompany, $lcName),
            'type' => 'symfony-vis-plugin',
            'description' => sprintf('VIS %s Plugin', $name),
            'autoload' => [
                'psr-4' => [
                    $namespace . '\\' => 'src/',
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
        if (isset($container->getExtensions()['twig'])) {
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    \dirname(__DIR__, 2).'/templates' => 'Vis{$name}Plugin',
                ],
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }
}
PHP;
        $extensionContent = str_replace(
            ['{$namespace}', '{$extensionName}', '{$name}'],
            [$namespace, $extensionName, $name],
            $extensionContent
        );
        $this->filesystem->dumpFile($path . '/src/DependencyInjection/' . $extensionName . '.php', $extensionContent);

        // Plugin class
        $pluginContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace {$namespace}\Plugin;

use JBSNewMedia\VisBundle\Attribute\VisPlugin;
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarHeader;
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarItem;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButtonDarkmode;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButtonSidebar;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearchTools;
use JBSNewMedia\VisBundle\Plugin\AbstractPlugin;
use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Contracts\Translation\TranslatorInterface;

#[VisPlugin(plugin: '{$lcName}')]
class {$name}Plugin extends AbstractPlugin
{
    public function __construct(
        protected Vis $vis,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    public function init(): void
    {
        $tool = $this->createTool();
        $tool->setTitle($this->translator->trans('main.title', domain: 'vis_{$lcName}'));
        $tool->addRole('ROLE_VIS_{$ucName}');
        $this->vis->addTool($tool);
    }

    public function setNavigation(): void
    {
        $item = new TopbarLiveSearchTools((string) $this->getPluginId());
        $item->setLabel($this->translator->trans('main.livesearch.tools', domain: 'vis'));
        $item->setLabelSearch($this->translator->trans('main.livesearch.placeholder', domain: 'vis'));
        $item->setVis($this->vis);
        $this->vis->addTopbar($item);

        $item = new TopbarButtonDarkmode((string) $this->getPluginId());
        $item->setLabel($this->translator->trans('main.toggle.darkmode', domain: 'vis'));
        $this->vis->addTopbar($item);

        $item = new TopbarButtonSidebar((string) $this->getPluginId());
        $item->setLabel($this->translator->trans('main.toggle.sidebar', domain: 'vis'));
        $this->vis->addTopbar($item);

        $item = new TopbarButtonSidebar((string) $this->getPluginId(), 'toggle_sidebar_end', 'end', ['display' => 'large']);
        $item->setLabel($this->translator->trans('main.toggle.sidebar', domain: 'vis'));
        $this->vis->addTopbar($item);

        $item = new SidebarHeader('{$lcName}', 'header_main', $this->translator->trans('navigation.header_main', domain: 'vis_{$lcName}'));
        $item->addRole('ROLE_VIS_{$ucName}');
        $item->addRole('ROLE_USER');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('{$lcName}', 'vis_{$lcName}_dashboard', $this->translator->trans('navigation.dashboard', domain: 'vis_{$lcName}'), 'vis_{$lcName}_dashboard');
        $item->generateIcon('fa-fw fa-solid fa-house');
        $item->addRole('ROLE_VIS_{$ucName}');
        $item->addRole('ROLE_USER');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('{$lcName}', 'vis_{$lcName}_table', $this->translator->trans('navigation.table', domain: 'vis_{$lcName}'));
        $item->generateIcon('fa-fw fa-solid fa-database');
        $item->addRole('ROLE_VIS_{$ucName}');
        $item->addRole('ROLE_USER');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('{$lcName}', 'vis_{$lcName}_table_datatable', $this->translator->trans('navigation.datatable', domain: 'vis_{$lcName}'), 'vis_{$lcName}_table_datatable');
        $item->generateCounter('5', 'secondary');
        $item->setParent('vis_{$lcName}_table');
        $item->addRole('ROLE_VIS_{$ucName}');
        $item->addRole('ROLE_USER');
        $this->vis->addSidebar($item);

        $item = new SidebarHeader('{$lcName}', 'header_admin', $this->translator->trans('navigation.header_admin', domain: 'vis_{$lcName}'));
        $item->addRole('ROLE_VIS_{$ucName}');
        $item->addRole('ROLE_USER');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('{$lcName}', 'vis_{$lcName}_user', $this->translator->trans('navigation.user', domain: 'vis_{$lcName}'), 'vis_{$lcName}_user');
        $item->generateIcon('fa-fw fa-solid fa-house');
        $item->addRole('ROLE_VIS_{$ucName}');
        $item->addRole('ROLE_USER');
        $this->vis->addSidebar($item, 'header_admin');
    }
}
PHP;
        $pluginContent = str_replace(
            ['{$namespace}', '{$name}', '{$lcName}', '{$ucName}'],
            [$namespace, $name, $lcName, $ucName],
            $pluginContent
        );
        $this->filesystem->dumpFile($path . '/src/Plugin/' . $name . 'Plugin.php', $pluginContent);

        // Controller
        $controllerContent = <<<'PHP'
<?php

declare(strict_types=1);

namespace {$namespace}\Controller;

use JBSNewMedia\VisBundle\Controller\VisAbstractController;
use JBSNewMedia\VisBundle\Service\Vis;
use {$namespace}\Service\{$name}Service;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class {$name}Controller extends VisAbstractController
{
    public function __construct(protected Vis $vis, protected TranslatorInterface $translator)
    {
    }

    #[Route('/vis/{$lcName}', name: 'vis_{$lcName}', methods: ['GET'])]
    #[Route('/vis/{$lcName}/dashboard', name: 'vis_{$lcName}_dashboard', methods: ['GET'])]
    public function dashboard(
        {$name}Service $service,
    ): Response {
        $this->vis->setTool('{$lcName}');
        $this->vis->setRoute('{$lcName}', 'vis_{$lcName}_dashboard');

        return $this->render('@Vis{$name}Plugin/tool/dashboard.html.twig', [
            'vis' => $this->vis,
            '{$lcName}Service' => $service,
        ]);
    }

    #[Route('/vis/{$lcName}/table_datatable', name: 'vis_{$lcName}_table_datatable', methods: ['GET'])]
    public function tableDatatable(): Response
    {
        $this->vis->setTool('{$lcName}');
        $this->vis->setRoute('{$lcName}', 'vis_{$lcName}_table_datatable');

        return $this->render('@Vis/content/datatable.html.twig', [
            'vis' => $this->vis,
            'datatable_title' => $this->translator->trans('datatable.{$lcName}.title', [], 'vis_{$lcName}'),
            'datatable_options' => [
                'apiUrl' => $this->generateUrl('vis_{$lcName}_table_datatable_api'),
                'perPage' => 5,
                'sorting' => [
                    'name' => 'asc',
                ],
            ],
            'datatable_language' => [
                'searchLabel' => $this->translator->trans('datatable.{$lcName}.search', [], 'vis_{$lcName}'),
            ],
        ]);
    }

    #[Route('/vis/{$lcName}/user', name: 'vis_{$lcName}_user', methods: ['GET'])]
    public function user(): Response
    {
        $this->vis->setTool('{$lcName}');
        $this->vis->setRoute('{$lcName}', 'vis_{$lcName}_user');

        return $this->render('@Vis{$name}Plugin/content/user.html.twig', [
            'vis' => $this->vis,
        ]);
    }
}
PHP;
        $controllerContent = str_replace(
            ['{$namespace}', '{$name}', '{$lcName}'],
            [$namespace, $name, $lcName],
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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class {$name}ApiController extends VisAbstractController
{
    public function __construct(protected TranslatorInterface $translator)
    {
    }

    #[Route('/vis/{$lcName}/table_datatable_api', name: 'vis_{$lcName}_table_datatable_api', methods: ['POST', 'GET'])]
    public function tableDatatableApi(): JsonResponse
    {
        if (!file_exists('../demo/demo.db')) {
            try {
                if (!is_dir('../demo/')) {
                    mkdir('../demo/');
                }
                $pdo = new \PDO('sqlite:../demo/demo.db');
                $result['data'] = [
                    ['id' => 1, 'name' => 'Tiger Nixon', 'age' => 61, 'city' => 'Edinburgh'],
                    ['id' => 2, 'name' => 'Garrett Winters', 'age' => 63, 'city' => 'Tokyo'],
                    ['id' => 3, 'name' => 'Ashton Cox', 'age' => 66, 'city' => 'San Francisco'],
                    ['id' => 4, 'name' => 'Cedric Kelly', 'age' => 22, 'city' => 'Edinburgh'],
                    ['id' => 5, 'name' => 'Airi Satou', 'age' => 33, 'city' => 'Tokyo'],
                    ['id' => 6, 'name' => 'Brielle Williamson', 'age' => 61, 'city' => 'New York'],
                    ['id' => 7, 'name' => 'Herrod Chandler', 'age' => 59, 'city' => 'San Francisco'],
                    ['id' => 8, 'name' => 'Rhona Davidson', 'age' => 55, 'city' => 'Tokyo'],
                    ['id' => 9, 'name' => 'Colleen Hurst', 'age' => 39, 'city' => 'San Francisco'],
                    ['id' => 10, 'name' => 'Sonya Frost', 'age' => 23, 'city' => 'Edinburgh'],
                    ['id' => 11, 'name' => 'Jena Gaines', 'age' => 30, 'city' => 'London'],
                    ['id' => 12, 'name' => 'Quinn Flynn', 'age' => 22, 'city' => 'Edinburgh'],
                    ['id' => 13, 'name' => 'Charde Marshall', 'age' => 36, 'city' => 'San Francisco'],
                    ['id' => 14, 'name' => 'Haley Kennedy', 'age' => 43, 'city' => 'London'],
                    ['id' => 15, 'name' => 'Tatyana Fitzpatrick', 'age' => 19, 'city' => 'London'],
                    ['id' => 16, 'name' => 'Michael Silva', 'age' => 66, 'city' => 'London'],
                    ['id' => 17, 'name' => 'Paul Byrd', 'age' => 64, 'city' => 'New York'],
                    ['id' => 18, 'name' => 'Gloria Little', 'age' => 59, 'city' => 'New York'],
                    ['id' => 19, 'name' => 'Bradley Greer', 'age' => 41, 'city' => 'London'],
                    ['id' => 20, 'name' => 'Dai Rios', 'age' => 35, 'city' => 'Edinburgh'],
                    ['id' => 21, 'name' => 'Jenette Caldwell', 'age' => 30, 'city' => 'New York'],
                    ['id' => 22, 'name' => 'Yuri Berry', 'age' => 40, 'city' => 'New York'],
                    ['id' => 23, 'name' => 'Caesar Vance', 'age' => 21, 'city' => 'New York'],
                    ['id' => 24, 'name' => 'Doris Wilder', 'age' => 23, 'city' => 'Sidney'],
                    ['id' => 25, 'name' => 'Angelica Ramos', 'age' => 47, 'city' => 'London'],
                    ['id' => 26, 'name' => 'Gavin Joyce', 'age' => 42, 'city' => 'Edinburgh'],
                    ['id' => 27, 'name' => 'Jennifer Chang', 'age' => 28, 'city' => 'Sidney'],
                    ['id' => 28, 'name' => 'Brenden Wagner', 'age' => 28, 'city' => 'San Francisco'],
                    ['id' => 29, 'name' => 'Fiona Green', 'age' => 48, 'city' => 'London'],
                    ['id' => 30, 'name' => 'Shou Itou', 'age' => 20, 'city' => 'Tokyo'],
                    ['id' => 31, 'name' => 'Michelle House', 'age' => 37, 'city' => 'Sidney'],
                    ['id' => 32, 'name' => 'Suki Burks', 'age' => 53, 'city' => 'London'],
                    ['id' => 33, 'name' => 'Prescott Bartlett', 'age' => 27, 'city' => 'London'],
                    ['id' => 34, 'name' => 'Gavin Cortez', 'age' => 22, 'city' => 'San Francisco'],
                    ['id' => 35, 'name' => 'Martena Mccray', 'age' => 46, 'city' => 'Edinburgh'],
                    ['id' => 36, 'name' => 'Unity Butler', 'age' => 47, 'city' => 'San Francisco'],
                    ['id' => 37, 'name' => 'Howard Hatfield', 'age' => 51, 'city' => 'San Francisco'],
                    ['id' => 38, 'name' => 'Hope Fuentes', 'age' => 41, 'city' => 'San Francisco'],
                    ['id' => 39, 'name' => 'Vivian Harrell', 'age' => 62, 'city' => 'San Francisco'],
                    ['id' => 40, 'name' => 'Timothy Mooney', 'age' => 37, 'city' => 'London'],
                    ['id' => 41, 'name' => 'Jackson Bradshaw', 'age' => 65, 'city' => 'New York'],
                    ['id' => 42, 'name' => 'Olivia Liang', 'age' => 64, 'city' => 'Sidney'],
                    ['id' => 43, 'name' => 'Bruno Nash', 'age' => 38, 'city' => 'London'],
                    ['id' => 44, 'name' => 'Sakura Yamamoto', 'age' => 37, 'city' => 'Tokyo'],
                    ['id' => 45, 'name' => 'Thor Walton', 'age' => 61, 'city' => 'New York'],
                    ['id' => 46, 'name' => 'Finn Camacho', 'age' => 47, 'city' => 'San Francisco'],
                    ['id' => 47, 'name' => 'Serge Baldwin', 'age' => 64, 'city' => 'London'],
                    ['id' => 48, 'name' => 'Zenaida Frank', 'age' => 63, 'city' => 'New York'],
                    ['id' => 49, 'name' => 'Zorita Serrano', 'age' => 56, 'city' => 'San Francisco'],
                    ['id' => 50, 'name' => 'Jennifer Acosta', 'age' => 43, 'city' => 'Edinburgh'],
                    ['id' => 51, 'name' => 'Cara Stevens', 'age' => 46, 'city' => 'New York'],
                    ['id' => 52, 'name' => 'Hermione Butler', 'age' => 47, 'city' => 'London'],
                    ['id' => 53, 'name' => 'Lael Greer', 'age' => 21, 'city' => 'London'],
                    ['id' => 54, 'name' => 'Jonas Alexander', 'age' => 30, 'city' => 'San Francisco'],
                    ['id' => 55, 'name' => 'Shad Decker', 'age' => 51, 'city' => 'Edinburgh'],
                    ['id' => 56, 'name' => 'Michael Bruce', 'age' => 29, 'city' => 'Singapore'],
                    ['id' => 57, 'name' => 'Donna Snider', 'age' => 27, 'city' => 'New York'],
                ];
                $stmt = $pdo->prepare('DROP TABLE IF EXISTS personen');
                $stmt->execute();
                $stmt = $pdo->prepare('CREATE TABLE personen (id INTEGER PRIMARY KEY, name TEXT, age INTEGER, city TEXT)');
                $stmt->execute();
                $stmt = $pdo->prepare('INSERT INTO personen (id, name, age, city) VALUES (:id, :name, :age, :city)');
                foreach ($result['data'] as $row) {
                    $stmt->execute($row);
                }
                $success = true;
            } catch (\PDOException) {
                $success = false;
            }
        }
        $result = [];
        $result['head'] = [];
        $result['head']['columns'] = [
            ['name' => $this->translator->trans('datatable.{$lcName}.columns.name', [], 'vis_{$lcName}'), 'sortable' => true, 'id' => 'name'],
            ['name' => $this->translator->trans('datatable.{$lcName}.columns.id', [], 'vis_{$lcName}'), 'sortable' => true, 'id' => 'id', 'hidden' => true],
            ['name' => $this->translator->trans('datatable.{$lcName}.columns.age', [], 'vis_{$lcName}'), 'id' => 'age'],
            ['name' => $this->translator->trans('datatable.{$lcName}.columns.city', [], 'vis_{$lcName}'), 'sortable' => true, 'id' => 'city'],
            ['name' => $this->translator->trans('datatable.{$lcName}.columns.options', [], 'vis_{$lcName}'), 'raw' => true, 'id' => 'options', 'class' => 'avalynx-datatable-options'],
        ];
        if (isset($_POST['sorting'])) {
            $result['sorting'] = json_decode((string) $_POST['sorting'], true);
            if (null === $result['sorting']) {
                $result['sorting'] = [];
            }
            if (false === $result['sorting']) {
                $result['sorting'] = [];
            }
        } else {
            $result['sorting'] = [];
        }
        if (isset($_POST['search'])) {
            $result['search']['value'] = $_POST['search'];
        } else {
            $result['search'] = [];
            $result['search']['value'] = '';
        }
        if (isset($_POST['page'])) {
            $result['page'] = (int) $_POST['page'];
        } else {
            $result['page'] = 1;
        }
        if (isset($_POST['perpage'])) {
            $result['perpage'] = (int) $_POST['perpage'];
        } else {
            $result['perpage'] = 10;
        }
        if (isset($_POST['searchisnew'])) {
            $result['searchisnew'] = (bool) $_POST['searchisnew'];
        } else {
            $result['searchisnew'] = false;
        }
        if (true === $result['searchisnew']) {
            $result['page'] = 1;
        }
        try {
            $pdo = new \PDO('sqlite:../demo/demo.db');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $where = [];
            $params = [];
            if ('' !== $result['search']['value']) {
                $where[] = '(name LIKE :search OR city LIKE :search)';
                $params[':search'] = '%'.$result['search']['value'].'%';
            }
            $orderBy = [];
            foreach ($result['sorting'] as $key => $sort) {
                $orderBy[] = $key.' '.$sort;
            }
            $orderBy = empty($orderBy) ? '' : 'ORDER BY '.implode(', ', $orderBy);
            $where = empty($where) ? '' : 'WHERE '.implode(' AND ', $where);
            $query = 'SELECT COUNT(*) FROM personen '.$where;
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $totalFiltered = $stmt->fetchColumn();
            $query = 'SELECT COUNT(*) FROM personen';
            $stmt = $pdo->query($query);
            $total = $stmt->fetchColumn();
            $result['data'] = [];
            $query = "SELECT * FROM personen $where $orderBy LIMIT :limit OFFSET :offset";
            $result['page'] = max(1, min($result['page'], ceil($totalFiltered / $result['perpage'])));
            $stmt = $pdo->prepare($query);
            $params[':limit'] = $result['perpage'];
            $params[':offset'] = ($result['page'] - 1) * $result['perpage'];
            $stmt->execute($params);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $row['name'] = $this->translator->trans($row['name'], [], 'vis_{$lcName}');
                $row['city'] = $this->translator->trans($row['city'], [], 'vis_{$lcName}');
                $row['options'] = sprintf(
                    '<a class="btn btn-sm btn-primary" title="%s">%s</a> <a class="btn btn-sm btn-primary" title="%s">%s</a> <a class="btn btn-sm btn-primary" title="%s">%s</a>',
                    $this->translator->trans('datatable.{$lcName}.actions.edit', [], 'vis_{$lcName}'),
                    $this->translator->trans('datatable.{$lcName}.actions.edit', [], 'vis_{$lcName}'),
                    $this->translator->trans('datatable.{$lcName}.actions.add', [], 'vis_{$lcName}'),
                    $this->translator->trans('datatable.{$lcName}.actions.add', [], 'vis_{$lcName}'),
                    $this->translator->trans('datatable.{$lcName}.actions.delete', [], 'vis_{$lcName}'),
                    $this->translator->trans('datatable.{$lcName}.actions.delete', [], 'vis_{$lcName}')
                );
                $result['data'][] = ['data' => $row, 'config' => ['test' => 'test_text'], 'class' => '', 'data_class' => ['options' => 'table-danger']];
            }
        } catch (\PDOException $e) {
            $result['error'] = $e->getMessage();
        }
        $result['count'] = [
            'total' => $total,
            'filtered' => $totalFiltered,
            'start' => 1 + ($result['page'] - 1) * $result['perpage'],
            'end' => min($totalFiltered, $result['page'] * $result['perpage']),
            'perpage' => $result['perpage'],
            'page' => min($result['page'], ceil($totalFiltered / $result['perpage'])),
        ];
        return $this->json($result);
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

class {$name}Service
{
    public function __construct()
    {
    }

    public function getTestMessage(): string
    {
        return 'dashboard.test_message';
    }
}
PHP;
        $serviceContent = str_replace(
            ['{$namespace}', '{$name}'],
            [$namespace, $name],
            $serviceContent
        );
        $this->filesystem->dumpFile($path . '/src/Service/' . $name . 'Service.php', $serviceContent);

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
class {$name}Command extends Command
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
        $this->filesystem->dumpFile($path . '/src/Command/' . $name . 'Command.php', $commandContent);

        // Twig Templates
        $dashboardTwig = <<<'TWIG'
{% extends '@Vis/tool/base.html.twig' %}
{% block vis_container %}
    {{ parent() }}
    <div class="card shadow">
        <div class="card-header">
            {{ 'dashboard.title'|trans({}, 'vis_{$lcName}') }}
        </div>
        <div class="card-body">
            {{ 'dashboard.welcome'|trans({}, 'vis_{$lcName}') }}
        </div>
        <div class="card-footer">
            <small class="text-muted">{{ {$lcName}Service.TestMessage|trans({}, 'vis_{$lcName}') }}</small>
        </div>
    </div>
{% endblock %}
TWIG;
        $dashboardTwig = str_replace(['{$lcName}'], [$lcName], $dashboardTwig);
        $this->filesystem->dumpFile($path . '/templates/tool/dashboard.html.twig', $dashboardTwig);

        $userTwig = <<<'TWIG'
{% extends '@Vis/tool/base.html.twig' %}
{% block vis_container %}
    {{ parent() }}
    <div class="card shadow">
        <div class="card-header">
            {{ 'user.title'|trans({}, 'vis_{$lcName}') }}
        </div>
        <div class="card-body">
            {{ 'user.description'|trans({}, 'vis_{$lcName}') }}
        </div>
    </div>
{% endblock %}
TWIG;
        $userTwig = str_replace(['{$lcName}'], [$lcName], $userTwig);
        $this->filesystem->dumpFile($path . '/templates/content/user.html.twig', $userTwig);

        // Translations
        $translationsDe = <<<YAML
main.title: "{$name}"
navigation:
    header_main: "Hauptbereich"
    dashboard: "Übersicht"
    table: "Tabelle"
    datatable: "Datentabelle"
    header_admin: "Administration"
    user: "Benutzer"
dashboard:
    title: "{$name} Dashboard"
    welcome: "Willkommen im Dashboard des VIS {$name} Plugins!"
    test_message: "Dies ist eine Testnachricht vom {$name}Service."
datatable:
    {$lcName}:
        title: "Übersicht der {$name}-Einträge"
        search: "Suche in {$name}-Einträgen:"
        columns:
            name: "Name"
            id: "ID"
            age: "Alter"
            city: "Stadt"
            options: "Optionen"
        actions:
            edit: "Bearbeiten"
            add: "Hinzufügen"
            delete: "Löschen"
user:
    title: "{$name} Benutzer"
    description: "Dies ist eine Demoseite für Benutzerinformationen."
YAML;
        $this->filesystem->dumpFile($path . '/translations/vis_' . $lcName . '.de.yaml', $translationsDe);

        $translationsEn = str_replace(
            ['Hauptbereich', 'Übersicht', 'Tabelle', 'Datentabelle', 'Administration', 'Benutzer', 'Willkommen im Dashboard des VIS', 'Dies ist eine Testnachricht vom', 'Übersicht der', 'Einträge', 'Suche in', 'Name', 'ID', 'Alter', 'Stadt', 'Optionen', 'Bearbeiten', 'Hinzufügen', 'Löschen', 'Dies ist eine Demoseite für Benutzerinformationen.'],
            ['Main Area', 'Dashboard', 'Table', 'Datatable', 'Administration', 'User', 'Welcome to the dashboard of the VIS', 'This is a test message from the', 'Overview of', 'Entries', 'Search in', 'Name', 'ID', 'Age', 'City', 'Options', 'Edit', 'Add', 'Delete', 'This is a demo page for user information.'],
            $translationsDe
        );
        $this->filesystem->dumpFile($path . '/translations/vis_' . $lcName . '.en.yaml', $translationsEn);

        // services.yaml
        $servicesYaml = <<<'YAML'
services:
    _defaults:
        autowire: true
        autoconfigure: true

    {$namespace}\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'
        tags: [ 'controller.service_arguments' ]

    {$namespace}\Controller\{$name}Controller:
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

    private function addRoutesToConfig(string $name, string $pluginDirName): void
    {
        $routesFile = $this->projectDir . '/config/routes.yaml';
        if (!$this->filesystem->exists($routesFile)) {
            return;
        }

        $lcName = strtolower($name);
        $routeName = sprintf('vis_%s_plugin', $lcName);
        $content = file_get_contents($routesFile);

        if (str_contains($content, $routeName . ':')) {
            return;
        }

        $newRoute = sprintf(
            "\n%s:\n    resource: ../%s/src/Controller/\n    type: attribute\n",
            $routeName,
            $pluginDirName
        );

        file_put_contents($routesFile, $content . $newRoute);
    }
}
