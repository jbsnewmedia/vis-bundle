<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Topbar;

class TopbarButtonDarkmode extends TopbarButton
{
    public function __construct(string $tool, string $id = 'toggle_darkmode_end', string $position = 'end')
    {
        parent::__construct($tool, $id);
        $this->setPosition($position);
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-simpleadmin-toggler-darkmode avalynx-simpleadmin-header-button');
        $this->setOnClick('avalynxSimpleAdminToggleDarkmode();');
        $this->setContent('<i class="fa-solid fa-circle-half-stroke fa-fw"></i>');
        $this->setLabel('Toggle Darkmode');
        $this->setOrder(100);
        $this->setContentFilter('raw');
    }
}
