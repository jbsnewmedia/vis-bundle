<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use JBSNewMedia\VisBundle\Entity\Item;
use JBSNewMedia\VisBundle\Entity\Sidebar\Sidebar;
use JBSNewMedia\VisBundle\Entity\Tool;
use JBSNewMedia\VisBundle\Entity\Topbar\Topbar;
use JBSNewMedia\VisBundle\Entity\Topbar\TopbarButtonDarkmode;
use JBSNewMedia\VisBundle\Entity\Topbar\TopbarButtonSidebar;
use JBSNewMedia\VisBundle\Entity\Topbar\TopbarDropdownProfile;
use JBSNewMedia\VisBundle\Entity\Topbar\TopbarLiveSearchTools;
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


    public function __construct(
        protected TranslatorInterface $translator,
        protected UrlGeneratorInterface $router,
        protected Security $security)
    {
        $user = $this->security->getUser();
        if (null !== $user) {
            $this->setRoles($user->getRoles());
        }
    }

    public function setTool(string $tool): self
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

    public function setToolId(string $toolId): void
    {
        $this->toolId = $toolId;
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

        $commonRoles = array_intersect($tool->getRoles(), $this->getRoles());
        if (empty($commonRoles)) {
            return false;
        }

        $this->tools[$tool->getId()] = $tool;
        $this->incToolsCounter();

        $item = new TopbarLiveSearchTools($tool->getId());
        $item->setLabel($this->translator->trans('main.livesearch.tools', domain: 'vis'));
        $item->setLabelSearch($this->translator->trans('main.livesearch.placeholder', domain: 'vis'));
        $item->setVis($this);
        $this->addTopbar($item);

        $item = new TopbarButtonDarkmode($tool->getId());
        $item->setLabel($this->translator->trans('main.toggle.darkmode', domain: 'vis'));
        $this->addTopbar($item);

        $item = new TopbarButtonSidebar($tool->getId());
        $item->setLabel($this->translator->trans('main.toggle.sidebar', domain: 'vis'));
        $this->addTopbar($item);

        $item = new TopbarButtonSidebar($tool->getId(), 'toggle_sidebar_end', 'end', ['display' => 'large']);
        $item->setLabel($this->translator->trans('main.toggle.sidebar', domain: 'vis'));
        $this->addTopbar($item);

        $item = new TopbarDropdownProfile($tool->getId(), 'profile_end');
        $item->setLabel($this->translator->trans('main.profile', domain: 'vis'));
        $item->setClass('btn btn-link justify-content-center align-items-center');
        $item->setContent('<img src="'.$this->router->generate('jbs_new_media_assets_composer', ['namespace' => 'jbsnewmedia', 'package' => 'vis-bundle', 'asset' => 'assets/img/profile.jpg']).'" class="h-100 rounded-circle" alt="profile-image">');
        $item->setOrder(100);
        $item->setData([
            /*
            'vis_settings' => [
                'route' => 'vis_settings',
                'routeParameters' => [],
                'icon' => '<i class="fa-solid fa-cog fa-fw"></i>',
                'label' => $this->translator->trans('main.profile.settings', domain: 'vis'),
            ],
            'vis_line_1' => [
                'route' => '',
                'routeParameters' => [],
                'icon' => '',
                'label' => '---',
            ],
            */
            'vis_logout' => [
                'route' => 'vis_logout',
                'routeParameters' => [],
                'icon' => '<i class="fa-solid fa-right-from-bracket fa-fw"></i>',
                'label' => $this->translator->trans('main.profile.logout', domain: 'vis'),
            ],
        ]);
        $item->setDataKey('vis_logout');
        $this->addTopbar($item);

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

    public function addTopbar(Topbar $item): bool
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
        $this->topbar[$item->getTool()][$item->getPosition()][$item->getId()] = $item;

        uasort($this->topbar[$item->getTool()][$item->getPosition()], [$this, 'sortItems']);

        return true;
    }

    /**
     * @return Topbar[]>
     */
    public function getTopbar(string $position, string $tool): array
    {
        if (!$this->isTool($tool)) {
            throw new \InvalidArgumentException('Vis: Tool "'.$tool.'" does not exist');
        }

        if (!isset($this->topbar[$tool])) {
            throw new \InvalidArgumentException('Vis: Tool "'.$tool.'" does not exist in topbar');
        }

        if (!isset($this->topbar[$tool][$position])) {
            return [];
        }

        return $this->topbar[$tool][$position];
    }

    public function addSidebar(Sidebar $item, string $parent = ''): bool
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
                'parent' => $item->getParent(),
            ];
        }

        if (null === $item->getCallbackFunction()) {
            $this->sidebar[$item->getTool()][$item->getId()] = $item;
        } else {
            if (null !== $item->getCallbackFunction()) {
                $callback = $item->getCallbackFunction();
                $callback($this, $item);
            }
        }

        uasort($this->sidebar[$item->getTool()], [$this, 'sortItems']);

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
     * @return Sidebar[]
     */
    public function getSidebar(string $tool): array
    {
        if (!$this->isTool($tool)) {
            throw new \InvalidArgumentException('Vis: Tool "'.$tool.'" does not exist');
        }

        if (!isset($this->sidebar[$tool])) {
            throw new \InvalidArgumentException('Vis: Tool "'.$tool.'" does not exist in sidebar');
        }

        return $this->sidebar[$tool];
    }

    public function setRoute(string $tool, string $route): void
    {
        $routes = explode('-', $route);
        $level = count($routes);

        if (!isset($this->routes[$tool][$routes[0]]) || !is_array($this->routes[$tool][$routes[0]])) {
            throw new \InvalidArgumentException('Vis: Sidebar route "'.$routes[0].'" does not exist');
        }
        $routeInfo = $this->routes[$tool][$routes[0]];
        if (is_array($routeInfo) && isset($routeInfo['parent']) && '' === $routeInfo['parent']) {
            $child = $this->sidebar[$tool][$routes[0]];
            $child->setActive(true);
        } else {
            if (!isset($this->sidebar[$tool][$routeInfo['parent']])) {
                throw new \InvalidArgumentException('Vis: Sidebar parent "'.$routeInfo['parent'].'" does not exist');
            }
            $parent = $this->sidebar[$tool][$routeInfo['parent']];
            $parent->setActive(true);
            $child = $this->sidebar[$tool][$routeInfo['parent']]->getChild($routes[0]);
            $child->setActive(true);
        }

        for ($i = 1; $i < $level; ++$i) {
            if (!$child->isChild($routes[$i])) {
                throw new \InvalidArgumentException('Vis: Sidebar parent "'.$routes[$i].'" does not exist');
            }
            $child = $child->getChild($routes[$i]);
            $child->setActive(true);
        }
    }

    protected function sortItems(Item $a, Item $b): int
    {
        return $a->getOrder() <=> $b->getOrder();
    }
}
