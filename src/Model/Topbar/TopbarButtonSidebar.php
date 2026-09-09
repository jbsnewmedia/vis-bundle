<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Topbar;

class TopbarButtonSidebar extends TopbarButton
{
    /**
     * @param array<string, string> $options
     */
    public function __construct(
        string $tool,
        string $id = 'toggle_sidebar_start',
        string $position = 'start',
        array $options = [],
    ) {
        parent::__construct($tool, $id);
        $options['display'] ??= 'small';

        if ('large' === $options['display']) {
            $options['class'] = 'd-flex d-md-none';
        } else {
            $options['class'] = 'd-none d-md-flex';
        }

        $this->setPosition($position);
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-admin-toggler-sidenav '.$options['class'].' avalynx-admin-header-button');
        $this->setOnClick('AvalynxAdminToggleSidenav();');
        $this->setContent('<i class="fa-solid fa-bars fa-fw"></i>');
        $this->setLabel('Toggle Sidebar');
        $this->setOrder(100);
        $this->setContentFilter('raw');
        $this->generateTemplate();
    }
}
