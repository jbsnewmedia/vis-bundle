<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Setting;

use JBSNewMedia\VisBundle\Model\Item;

class Setting extends Item
{
    protected string $icon = '';

    protected string $description = '';

    public function __construct(string $tool, string $id, string $label, string $route = '')
    {
        parent::__construct($tool, $id);
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
