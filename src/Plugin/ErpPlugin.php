<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Plugin;

use JBSNewMedia\VisBundle\Entity\Sidebar\SidebarHeader;
use JBSNewMedia\VisBundle\Entity\Sidebar\SidebarItem;
use JBSNewMedia\VisBundle\Entity\Tool;
use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Contracts\Translation\TranslatorInterface;

class ErpPlugin extends AbstractPlugin
{
    public function __construct(public Vis $vis, protected TranslatorInterface $translator)
    {
        parent::__construct();
    }

    public function init(): void
    {
        $tool = new Tool('erp');
        $tool->setTitle($this->translator->trans('main.title', domain: 'vis_erp'));
        $this->vis->addTool($tool);
    }

    public function setNavigation(): void
    {
        $item = new SidebarHeader('erp', 'header_main', 'Main');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('erp', 'dashboard', 'Dashboard', 'vis_manager_dashboard');
        $item->generateIcon('fa-fw fa-solid fa-house');
        $item->generateBadge('Error', 'danger');
        $item->generateCounter('5', 'secondary');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('erp', 'vis', 'Vis');
        $item->generateIcon('fa-fw fa-solid fa-house');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('erp', 'user', 'User', 'vis_manager_user');
        $item->generateIcon('fa-fw fa-solid fa-house');
        $item->generateBadge('Error', 'danger');
        $item->generateCounter('5', 'secondary');
        $item->setParent('vis');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('erp', 'vis2', 'Vis2', 'vis_manager_user');
        $item->generateIcon('fa-fw fa-solid fa-house');
        $item->setParent('vis');
        $this->vis->addSidebar($item);

        $item = new SidebarItem('erp', 'user2', 'User2', 'vis_manager_user');
        $item->generateIcon('fa-fw fa-solid fa-house');
        $item->generateBadge('Error', 'danger');
        $item->generateCounter('5', 'secondary');
        $item->setParent('vis-vis2');
        $this->vis->addSidebar($item);

        /*

        $this->vis->addSidebarItem((new SidebarItem('dashboard'))
            ->setLabel('Dashboard')
            ->setRoute('vis_manager_dashboard')
            ->setIcon('<i class="fa-fw fa-solid fa-house"></i>')
            ->setBadge('<span class="ms-1 badge text-bg-danger">Error</span>')
            ->setCount('<span class="ms-1 badge rounded-pill text-bg-secondary">5</span>')
        , 'manager');
        $this->vis->addSidebarItem((new SidebarItem('dashboard2'))
            ->setLabel('Dashboard2')
            ->setRoute('vis_manager_dashboard')
            ->setIcon('<i class="fa-fw fa-solid fa-house"></i>')
            ->setBadge('<span class="ms-1 badge text-bg-danger">Error</span>')
            ->setCount('<span class="ms-1 badge rounded-pill text-bg-secondary">5</span>')
            ->setRouterParams([
                'namespace' => 'test',
                'package' => 'vis',
                'asset' => 'composer'])
            , 'manager');
        */
    }
}
