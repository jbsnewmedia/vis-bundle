<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use JBSNewMedia\VisBundle\Model\Item;
use JBSNewMedia\VisBundle\Model\Setting\Setting;
use JBSNewMedia\VisBundle\Model\Sidebar\Sidebar;
use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Model\Topbar\Topbar;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButtonDarkmode;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdownLocale;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Vis
{
    use \JBSNewMedia\VisBundle\Trait\RolesTrait;
    protected string $tool = '';

    /**
     * @var Tool[]
     */
    protected array $tools = [];

    protected string $toolId = '';

    /**
     * @var array<string, string>
     */
    protected array $clients = [];

    protected int $toolsCounter = 0;

    /**
     * @var array<string, array<string, Topbar[]>>
     */
    protected array $topbar = [];

    /**
     * @var array<string, Sidebar[]>
     */
    protected array $sidebar = [];

    /**
     * @var array<string, Setting[]>
     */
    protected array $settings = [];

    /**
     * @var array<string, array<string, array<string, string>|string>>
     */
    protected array $routes = [];

    protected bool $sidebarClosed = false;

    protected string $selectedClientId = '';

    public const DEFAULT_THEME = 'vis';

    public const SESSION_KEY_THEME = 'vis_theme_slug';

    /**
     * @param string[] $locales
     */
    public function __construct(
        protected TranslatorInterface $translator,
        protected UrlGeneratorInterface $router,
        protected Security $security,
        protected string $projectDir,
        protected array $locales = ['en'],
        protected string $defaultLocale = 'en',
        protected ?RequestStack $requestStack = null,
        protected string $assetsPath = 'avalynx',
        protected string $assetsSrcPath = 'dist',
        protected string $theme = self::DEFAULT_THEME,
    ) {
        $user = $this->security->getUser();
        if (null !== $user) {
            $this->setRoles($user->getRoles());
        }
    }

    public function setTool(string $tool, int $priority = 100): self
    {
        if (!$this->isTool($tool)) {
            throw new \InvalidArgumentException('Vis: Tool "'.$tool.'" does not exist');
        }
        $this->tool = $tool;

        $this->setToolId($this->tools[$tool]->getId());

        return $this;
    }

    public function getTool(): string
    {
        return $this->tool;
    }

    public function isTool(string $tool): bool
    {
        return isset($this->tools[$tool]);
    }

    public function setToolId(string $toolId): self
    {
        $this->toolId = $toolId;

        return $this;
    }

    public function getToolId(): string
    {
        return $this->toolId;
    }

    public function addTool(Tool $tool): bool
    {
        if ([] === $tool->getRoles()) {
            $tool->addRole('ROLE_USER');
        }

        if (in_array($tool->getId(), ['register', 'login', 'logout', 'profile', 'settings'], true)) {
            return false;
        }

        if ($tool->isMerge() && isset($this->tools[$tool->getId()])) {
            if ($tool->getPriority() > $this->tools[$tool->getId()]->getPriority()) {
                $this->tools[$tool->getId()]->setPriority($tool->getPriority());
                $this->tools[$tool->getId()]->setTitle($tool->getTitle());
                foreach ($tool->getRoles() as $role) {
                    $this->tools[$tool->getId()]->addRole($role);
                }
            }

            return true;
        }

        $this->tools[$tool->getId()] = $tool;
        $this->incToolsCounter();

        return true;
    }

    /**
     * @return Tool[]
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * @param array<string, string> $clients
     */
    public function setClients(array $clients): void
    {
        $this->clients = $clients;
    }

    /**
     * @return array<string, string>
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    public function setSelectedClientId(string $clientId): void
    {
        $this->selectedClientId = $clientId;
        $this->requestStack?->getSession()->set('_vis_client_id', $clientId);
    }

    public function getSelectedClientId(): string
    {
        if ('' === $this->selectedClientId) {
            $sessionClientId = $this->requestStack?->getSession()->get('_vis_client_id', '');
            $this->selectedClientId = is_string($sessionClientId) ? $sessionClientId : '';
        }

        return $this->selectedClientId;
    }

    public function getSelectedClientTitle(): ?string
    {
        $id = $this->getSelectedClientId();

        return $this->clients[$id] ?? null;
    }

    protected function incToolsCounter(): void
    {
        ++$this->toolsCounter;
    }

    protected function decToolsCounter(): void
    {
        --$this->toolsCounter;
    }

    public function getToolsCounter(): int
    {
        return $this->toolsCounter;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @return string[]
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function getAssetsPath(): string
    {
        return $this->assetsPath;
    }

    public function getAssetsSrcPath(): string
    {
        return $this->assetsSrcPath;
    }

    /**
     * Returns the active theme id. A valid session theme
     * (switchable at runtime) wins over the configured default.
     */
    public function getTheme(): string
    {
        $session = $this->requestStack?->getSession();
        if (null !== $session) {
            $slug = $session->get(self::SESSION_KEY_THEME, '');
            $slug = is_string($slug) ? trim($slug) : '';
            if ('' !== $slug && $this->isTheme($slug)) {
                return $slug;
            }
        }

        if ($this->isTheme($this->theme)) {
            return $this->theme;
        }

        return self::DEFAULT_THEME;
    }

    public function setTheme(string $theme): void
    {
        $session = $this->requestStack?->getSession();
        if (null === $session) {
            return;
        }

        if (!$this->isTheme($theme)) {
            $session->remove(self::SESSION_KEY_THEME);

            return;
        }

        $session->set(self::SESSION_KEY_THEME, $theme);
    }

    public function resetTheme(): void
    {
        $this->requestStack?->getSession()->remove(self::SESSION_KEY_THEME);
    }

    public function isTheme(string $theme): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $theme)
            && is_dir($this->getThemesPath().'/'.$theme);
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function getThemes(): array
    {
        $themes = [];
        $dir = $this->getThemesPath();
        if (!is_dir($dir)) {
            return $themes;
        }

        $entries = scandir($dir) ?: [];
        foreach ($entries as $entry) {
            if (str_starts_with($entry, '.') || !is_dir($dir.'/'.$entry) || !$this->isTheme($entry)) {
                continue;
            }

            $themes[] = [
                'id' => $entry,
                'name' => ucfirst($entry),
            ];
        }

        usort($themes, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $themes;
    }

    public function getThemesPath(): string
    {
        return \dirname(__DIR__, 2).'/templates/themes';
    }

    /**
     * Returns the asset-composer path of the compiled (native) theme CSS,
     * or null when the active theme has no compiled CSS (falls back to
     * the plain Bootstrap dist file).
     */
    public function getThemeCss(): ?string
    {
        $theme = $this->getTheme();
        if (self::DEFAULT_THEME === $theme) {
            return null;
        }

        if (!is_file(\dirname(__DIR__, 2).'/assets/themes/'.$theme.'/css/theme.min.css')) {
            return null;
        }

        return 'jbsnewmedia/vis-bundle/assets/themes/'.$theme.'/css/theme.min.css';
    }

    public function getThemeAssetsPath(): string
    {
        return 'jbsnewmedia/vis-bundle/assets/themes';
    }

    public function addTopbar(Topbar $item): bool
    {
        if ($item instanceof TopbarDropdownLocale && count($this->locales) <= 1) {
            return false;
        }

        if ([] === $item->getRoles()) {
            $item->addRole('ROLE_USER');
        }

        if ('' !== $item->getRoute()) {
            $this->routes[$item->getTool()][$item->getRoute()] = [
                'route' => $item->getRoute(),
                'parent' => '',
            ];
        }
        $this->topbar[$item->getTool()][$item->getPosition()][$item->getId()] = $item;

        uasort($this->topbar[$item->getTool()][$item->getPosition()], $this->sortItems(...));

        return true;
    }

    /**
     * @return array<string, Topbar>
     */
    public function getTopbar(string $position, string $tool): array
    {
        if (!$this->isTool($tool)) {
            return [];
        }

        if (!isset($this->topbar[$tool])) {
            return [];
        }

        if (!isset($this->topbar[$tool][$position])) {
            return [];
        }

        /** @var array<string, Topbar> $result */
        $result = $this->topbar[$tool][$position];

        return $result;
    }

    /**
     * @return Topbar[]
     */
    public function getTopbarGuest(string $position): array
    {
        $items = $this->topbar['simple'][$position] ?? [];

        if ('end' === $position) {
            if (!isset($items['toggle_darkmode_end'])) {
                $item = new TopbarButtonDarkmode('simple');
                $item->setLabel($this->translator->trans('main.toggle.darkmode', domain: 'vis'));
                $items['toggle_darkmode_end'] = $item;
            }

            if (!isset($items['dropdown_locale']) && count($this->locales) > 1) {
                $locale = $this->getTranslator()->getLocale();
                $item = new TopbarDropdownLocale('simple');
                $item->setLabel($this->translator->trans('main.locale', domain: 'vis'));
                $item->setContentFilter('raw');
                $item->setContent('<i class="fa-solid fa-globe fa-fw"></i>');
                $item->setDataKey($locale);
                $data = [];
                foreach ($this->getLocales() as $l) {
                    $data[$l] = [
                        'route' => 'vis_api_locale',
                        'routeParameters' => ['_locale' => $l, 'timestamp' => '__TIMESTAMP__'],
                        'icon' => '',
                        'label' => $this->translator->trans('locale.'.$l, domain: 'vis'),
                    ];
                }
                $item->setData($data);
                $items['dropdown_locale'] = $item;
            }

            uasort($items, $this->sortItems(...));
        }

        return $items;
    }

    public function addSidebar(Sidebar $item, string $parent = ''): bool
    {
        if ([] === $item->getRoles()) {
            $item->addRole('ROLE_USER');
        }

        if ('' !== $parent && '' !== $item->getParent() && $item->getParent() !== $parent) {
            throw new \InvalidArgumentException('Vis: Conflicting sidebar parent provided. Item has parent "'.$item->getParent().'" but addSidebar() received "'.$parent.'". Use only one method and ensure IDs match.');
        }

        if ('' !== $parent) {
            $item->setParent($parent);
        }

        $commonRoles = array_intersect($item->getRoles(), $this->getRoles());
        if (empty($commonRoles)) {
            return false;
        }

        if ('' !== $item->getRoute()) {
            $this->routes[$item->getTool()][$item->getRoute()] = [
                'route' => $item->getRoute(),
                'parent' => $item->getParent(),
            ];
        }

        if (null === $item->getCallbackFunction()) {
            $this->sidebar[$item->getTool()][$item->getId()] = $item;
        } else {
            $callback = $item->getCallbackFunction();
            $callback($this, $item);
        }

        uasort($this->sidebar[$item->getTool()], $this->sortItems(...));

        return true;
    }

    /**
     * @param Sidebar[] $sidebar
     */
    public function setSidebar(string $tool, array $sidebar): void
    {
        $this->sidebar[$tool] = $sidebar;
    }

    /**
     * @return array<string, Sidebar>
     */
    public function addSetting(Setting $item): bool
    {
        if ([] === $item->getRoles()) {
            $item->addRole('ROLE_USER');
        }

        $commonRoles = array_intersect($item->getRoles(), $this->getRoles());
        if (empty($commonRoles)) {
            return false;
        }

        if ('' !== $item->getRoute()) {
            $this->routes[$item->getTool()][$item->getRoute()] = [
                'route' => $item->getRoute(),
                'parent' => '',
            ];
        }

        $this->settings[$item->getTool()][$item->getId()] = $item;

        uasort($this->settings[$item->getTool()], $this->sortItems(...));

        return true;
    }

    /**
     * @return Setting[]
     */
    public function getSettings(string $tool): array
    {
        if (!$this->isTool($tool)) {
            return [];
        }

        if (!isset($this->settings[$tool])) {
            return [];
        }

        /** @var array<string, Setting> $result */
        $result = $this->settings[$tool];

        return $result;
    }

    public function getSidebar(string $tool): array
    {
        if (!$this->isTool($tool)) {
            throw new \InvalidArgumentException('Vis: Tool "'.$tool.'" does not exist');
        }

        if (!isset($this->sidebar[$tool])) {
            throw new \InvalidArgumentException('Vis: Tool "'.$tool.'" does not exist in sidebar');
        }

        /** @var array<string, Sidebar> $result */
        $result = $this->sidebar[$tool];

        return $result;
    }

    public function setRoute(string $tool, string $route): void
    {
        $routes = explode('-', $route);
        $level = count($routes);

        if (!isset($this->routes[$tool][$routes[0]])) {
            throw new \InvalidArgumentException('Vis: Sidebar route "'.$routes[0].'" does not exist');
        }
        $routeInfo = $this->routes[$tool][$routes[0]];
        $child = null;
        if (is_array($routeInfo) && isset($routeInfo['parent']) && '' === $routeInfo['parent']) {
            $child = $this->sidebar[$tool][$routes[0]];
            $child->setActive(true);
        } elseif (is_array($routeInfo) && isset($routeInfo['parent'])) {
            if (!isset($this->sidebar[$tool][$routeInfo['parent']])) {
                throw new \InvalidArgumentException('Vis: Sidebar parent "'.$routeInfo['parent'].'" does not exist');
            }
            $parent = $this->sidebar[$tool][$routeInfo['parent']];
            $parent->setActive(true);
            $child = $this->sidebar[$tool][$routeInfo['parent']]->getChild($routes[0]);
            $child->setActive(true);
        }

        if (null === $child) {
            return;
        }

        for ($i = 1; $i < $level; ++$i) {
            if (!$child->isChild($routes[$i])) {
                throw new \InvalidArgumentException('Vis: Sidebar parent "'.$routes[$i].'" does not exist');
            }
            $child = $child->getChild($routes[$i]);
            $child->setActive(true);
        }
    }

    public function sortItems(Item $a, Item $b): int
    {
        return $a->getOrder() <=> $b->getOrder();
    }

    public function setSidebarClosed(bool $sidebarClosed): self
    {
        $this->sidebarClosed = $sidebarClosed;

        return $this;
    }

    public function isSidebarClosed(): bool
    {
        return $this->sidebarClosed;
    }

    /**
     * @return array<string, string>
     */
    public function getAllAvailableRoles(): array
    {
        $roles = [];

        foreach ($this->getRoles() as $role) {
            $roles[$role] = $role;
        }

        foreach ($this->tools as $tool) {
            foreach ($tool->getRoles() as $role) {
                $roles[$role] = $role;
            }
        }

        foreach ($this->sidebar as $toolSidebar) {
            foreach ($toolSidebar as $item) {
                $this->collectRoles($item, $roles);
            }
        }

        foreach ($this->topbar as $positionTopbar) {
            foreach ($positionTopbar as $toolTopbar) {
                foreach ($toolTopbar as $item) {
                    foreach ($item->getRoles() as $role) {
                        $roles[$role] = $role;
                    }
                }
            }
        }

        ksort($roles);

        return $roles;
    }

    /**
     * @param array<string, string> $roles
     */
    private function collectRoles(Sidebar $item, array &$roles): void
    {
        foreach ($item->getRoles() as $role) {
            $roles[$role] = $role;
        }

        foreach ($item->getChildren() as $child) {
            $this->collectRoles($child, $roles);
        }
    }
}
