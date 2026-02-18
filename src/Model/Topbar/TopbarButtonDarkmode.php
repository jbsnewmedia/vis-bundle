<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Topbar;

class TopbarButtonDarkmode extends TopbarButton
{
    public function __construct(
        string $tool,
        string $id = 'toggle_darkmode_end',
        string $position = 'end',
    ) {
        parent::__construct($tool, $id);
        $this->setPosition($position);
        $this->setTemplate('@Vis/topbar/button_darkmode.html.twig');
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-simpleadmin-toggler-darkmode avalynx-simpleadmin-header-button');
        $this->setContent('<i class="fa-solid fa-circle-half-stroke fa-fw"></i>');
        $this->setLabel('Toggle Darkmode');
        $this->setOrder(100);
        $this->setOnClick('avalynxSimpleAdminToggleDarkmode()');
        $this->setContentFilter('raw');
        $this->generateTemplate();
    }
}
