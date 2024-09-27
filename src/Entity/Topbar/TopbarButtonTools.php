<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Topbar;

class TopbarButtonTools extends TopbarButton
{
    public function __construct(
        string $tool,
        string $id = 'toggle_tools_end',
        string $position = 'end',
    ) {
        parent::__construct($tool, $id);
        $this->setType('buttontools');
        $this->setPosition($position);
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-simpleadmin-toggler-sidenav  avalynx-simpleadmin-header-button');
        $this->setOnClick('avalynxSimpleAdminToggleSidenav();');
        $this->setContent('Tools');
        $this->setLabel('Toggle Sidebar');
        $this->setOrder(100);
        $this->setContentFilter('raw');
        $this->generateTemplate();
    }
}
