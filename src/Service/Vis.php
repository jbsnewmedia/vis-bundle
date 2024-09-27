<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use JBSNewMedia\VisBundle\Entity\Item;
use JBSNewMedia\VisBundle\Entity\Sidebar\Sidebar;
use JBSNewMedia\VisBundle\Entity\Tool;
use JBSNewMedia\VisBundle\Entity\Topbar\Topbar;
use JBSNewMedia\VisBundle\Entity\Topbar\TopbarButtonDarkmode;
use JBSNewMedia\VisBundle\Entity\Topbar\TopbarButtonSidebar;
use JBSNewMedia\VisBundle\Entity\Topbar\TopbarDropdown;
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

    /**
     * @var array<string, array<string, Topbar[]>>
     */
    protected array $topbar = [];

    /**
     * @var array<string, Sidebar[]>
     */
    protected array $sidebar = [];

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

    public function addTool(Tool $tool): bool
    {
        if ($tool->getRoles()===[]) {
            $tool->addRole('ROLE_USER');
        }

        $commonRoles = array_intersect($tool->getRoles(), $this->getRoles());
        if (empty($commonRoles)) {
            return false;
        }

        $this->tools[$tool->getId()] = $tool;

        $item = new TopbarButtonDarkmode($tool->getId());
        $item->setLabel($this->translator->trans('main.toggle.darkmode', domain: 'vis'));
        $this->addTopbar($item);

        $item = new TopbarButtonSidebar($tool->getId(), 'toggle_darkmode_start', 'start');
        $item->setLabel($this->translator->trans('main.toggle.sidebar', domain: 'vis'));
        $this->addTopbar($item);

        $item = new TopbarButtonSidebar($tool->getId());
        $item->setLabel($this->translator->trans('main.toggle.sidebar', domain: 'vis'));
        $this->addTopbar($item);

        $item = new TopbarDropdown($tool->getId(), 'profile_end');
        $item->setLabel($this->translator->trans('main.profile', domain: 'vis'));
        $item->setClass('btn btn-link justify-content-center align-items-center');
        $item->setContent('<img src="'.$this->router->generate('jbs_new_media_assets_composer', ['namespace' => 'jbsnewmedia', 'package' => 'vis-bundle', 'asset' => 'assets/img/profile.jpg']).'" class="h-100 rounded-circle" alt="profile-image">');
        $item->setContentFilter('raw');
        $item->setOrder(100);
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

    public function addTopbar(Topbar $item): bool
    {
        if ($item->getRoles()===[]) {
            $item->addRole('ROLE_USER');
        }

        $commonRoles = array_intersect($item->getRoles(), $this->getRoles());
        if (empty($commonRoles)) {
            return false;
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
        if ($item->getRoles()===[]) {
            $item->addRole('ROLE_USER');
        }

        $commonRoles = array_intersect($item->getRoles(), $this->getRoles());
        if (empty($commonRoles)) {
            return false;
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

        if (!isset($this->sidebar[$tool][$routes[0]])) {
            throw new \InvalidArgumentException('Vis: Sidebar route "'.$routes[0].'" does not exist');
        }
        $child = $this->sidebar[$tool][$routes[0]];
        $child->setActive(true);

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
