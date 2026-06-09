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
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-admin-toggler-darkmode avalynx-admin-header-button d-none d-sm-flex');
        $this->setContent('');
        $this->setLabel('Toggle Darkmode');
        $this->setOrder(100);
        $this->setOnClick('AvalynxAdminToggleDarkmode()');
        $this->setContentFilter('raw');
        $this->generateTemplate();
    }
}
