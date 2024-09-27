<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Sidebar;

use JBSNewMedia\VisBundle\Entity\Item;
use JBSNewMedia\VisBundle\Service\Vis;

class Sidebar extends Item
{
    protected string $parent = '';

    /**
     * @var SidebarItem[]
     */
    protected array $children = [];

    public function __construct(string $tool, string $id)
    {
        parent::__construct($tool, $id);
    }

    public function generateTemplate(): void
    {
        if ('' === $this->getTemplate()) {
            if (file_exists(__DIR__.'/../../../templates/sidebar/'.$this->getType().'.html.twig')) {
                $this->setTemplate('@VisBundle/sidebar/'.$this->getType().'.html.twig');
            } else {
                throw new \Exception('Template not found: topbar/'.$this->getType().'.html.twig');
            }
        }
    }

    public function getParent(): string
    {
        return $this->parent;
    }

    public function setParent(string $parent): void
    {
        $this->parent = $parent;

        $this->callbackFunction = function (Vis $vis, SidebarItem $item) {
            $parents = explode('-', $item->getParent());
            $sidebar = $vis->getSidebar($item->getTool());
            $level = count($parents);

            if (!isset($sidebar[$parents[0]])) {
                throw new \InvalidArgumentException('Vis: Sidebar parent "'.$parents[0].'" does not exist');
            }
            $child = $sidebar[$parents[0]];

            for ($i = 1; $i < $level; ++$i) {
                if (!$child->isChild($parents[$i])) {
                    throw new \InvalidArgumentException('Vis: Sidebar parent "'.$parents[$i].'" does not exist');
                }
                $child = $child->getChild($parents[$i]);
            }

            $child->addChild($item);
            $vis->setSidebar($item->getTool(), $sidebar);
        };
    }

    public function addChild(SidebarItem $child): void
    {
        $this->children[$child->getId()] = $child;
    }

    public function isChild(string $id): bool
    {
        return isset($this->children[$id]);
    }

    public function getChild(string $id): SidebarItem
    {
        if (!isset($this->children[$id])) {
            throw new \InvalidArgumentException('Vis: Sidebar child "'.$id.'" does not exist');
        }

        return $this->children[$id];
    }

    /**
     * @param SidebarItem[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    /**
     * @return SidebarItem[] $children
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
