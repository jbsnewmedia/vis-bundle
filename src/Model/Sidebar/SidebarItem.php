<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Sidebar;

class SidebarItem extends Sidebar
{
    public \Closure $callbackFunction;

    protected string $icon = '';

    protected string $badge = '';

    protected string $counter = '';

    public function __construct(string $tool, string $id, string $label, string $route = '')
    {
        parent::__construct($tool, $id);
        $this->setType('item');
        $this->generateTemplate();
        $this->setLabel($label);
        $this->setRoute($route);
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function generateIcon(string $class): void
    {
        $this->setIcon('<i class="'.$class.'"></i>');
    }

    public function getBadge(): string
    {
        return $this->badge;
    }

    public function setBadge(string $badge): void
    {
        $this->badge = $badge;
    }

    public function generateBadge(string $badge, string $type = 'primary'): void
    {
        $this->setBadge('<span class="ms-1 badge text-bg-'.$type.'">'.$badge.'</span>');
    }

    public function getCounter(): string
    {
        return $this->counter;
    }

    public function setCounter(string $counter): void
    {
        $this->counter = $counter;
    }

    public function generateCounter(string $counter, string $type = 'primary'): void
    {
        $this->setCounter('<span class="ms-1 badge rounded-pill text-bg-'.$type.'">'.$counter.'</span>');
    }
}
