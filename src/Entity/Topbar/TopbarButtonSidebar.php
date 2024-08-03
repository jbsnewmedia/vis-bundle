<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Topbar;

class TopbarButtonSidebar extends TopbarButton
{
    public function __construct(string $tool, string $id = 'toggle_sidebar_end', string $position = 'end')
    {
        parent::__construct($tool, $id);
        $this->setPosition($position);
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-simpleadmin-toggler-sidenav d-none d-md-flex avalynx-simpleadmin-header-button');
        $this->setOnClick('avalynxSimpleAdminToggleSidenav();');
        $this->setContent('<i class="fa-solid fa-align-left fa-fw"></i>');
        $this->setLabel('Toggle Sidebar');
        $this->setOrder(100);
        $this->setContentFilter('raw');
    }
}
