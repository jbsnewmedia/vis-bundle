<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use JBSNewMedia\VisBundle\Model\Item;
use JBSNewMedia\VisBundle\Model\Sidebar\Sidebar;
use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Model\Topbar\Topbar;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdownLocale;
use Symfony\Bundle\SecurityBundle\Security;
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
     * @var array<string, array<string, array<string, string>|string>>
     */
    protected array $routes = [];

    /**
     * @param string[] $locales
     */
    public function __construct(
        protected TranslatorInterface $translator,
        protected UrlGeneratorInterface $router,
        protected Security $security,
        protected array $locales = ['en'],
        protected string $defaultLocale = 'en',
        protected bool $topbarDarkmode = true,
        protected bool $topbarLocale = true,
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
        if ('' === $tool) {
            $tool = '_guest_';
        }

        if (!$this->isTool($tool) && '_guest_' !== $tool) {
            throw new \InvalidArgumentException('Vis: Tool "'.$tool.'" does not exist');
        }

        if ('end' === $position) {
            $this->addTopbarDefault($tool);
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

    protected function addTopbarDefault(string $tool): void
    {
        if ($this->topbarDarkmode && !isset($this->topbar[$tool]['end']['toggle_darkmode_end'])) {
            $item = new \JBSNewMedia\VisBundle\Model\Topbar\TopbarButtonDarkmode($tool);
            $item->setLabel($this->translator->trans('main.toggle.darkmode', domain: 'vis'));
            $this->addTopbar($item);
        }

        if ($this->topbarLocale && !isset($this->topbar[$tool]['end']['dropdown_locale']) && count($this->locales) > 1) {
            $locale = $this->translator->getLocale();
            $item = new \JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdownLocale($tool);
            $item->setLabel($this->translator->trans('main.locale', domain: 'vis'));
            $item->setContent(strtoupper($locale));
            $item->setDataKey($locale);
            $data = [];
            foreach ($this->locales as $l) {
                $data[$l] = [
                    'route' => 'vis_api_locale',
                    'routeparameters' => ['_locale' => $l],
                    'label' => $this->translator->trans('locale.'.$l, domain: 'vis'),
                ];
            }
            $item->setData($data);
            $this->addTopbar($item);
        }
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
}
