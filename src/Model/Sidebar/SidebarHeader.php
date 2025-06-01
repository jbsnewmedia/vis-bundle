<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Sidebar;

class SidebarHeader extends Sidebar
{
    public function __construct(string $tool, string $id, string $label)
    {
        parent::__construct($tool, $id);
        $this->setType('header');
        $this->generateTemplate();
        $this->setLabel($label);
    }
}
