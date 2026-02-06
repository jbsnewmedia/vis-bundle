<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model\Sidebar;

use JBSNewMedia\VisBundle\Model\Sidebar\SidebarItem;
use PHPUnit\Framework\TestCase;

class SidebarItemTest extends TestCase
{
    public function testConstructor(): void
    {
        $item = new SidebarItem('tool', 'id', 'My Item', 'my_route');
        $this->assertEquals('item', $item->getType());
        $this->assertEquals('My Item', $item->getLabel());
        $this->assertEquals('my_route', $item->getRoute());
        $this->assertEquals('@Vis/sidebar/item.html.twig', $item->getTemplate());
    }

    public function testIcon(): void
    {
        $item = new SidebarItem('tool', 'id', 'Label');
        $item->setIcon('fa fa-user');
        $this->assertEquals('fa fa-user', $item->getIcon());

        $item->generateIcon('fa fa-home');
        $this->assertEquals('<i class="fa fa-home"></i>', $item->getIcon());
    }

    public function testBadge(): void
    {
        $item = new SidebarItem('tool', 'id', 'Label');
        $item->setBadge('new');
        $this->assertEquals('new', $item->getBadge());

        $item->generateBadge('123', 'danger');
        $this->assertEquals('<span class="ms-1 badge text-bg-danger">123</span>', $item->getBadge());
    }

    public function testCounter(): void
    {
        $item = new SidebarItem('tool', 'id', 'Label');
        $item->setCounter('5');
        $this->assertEquals('5', $item->getCounter());

        $item->generateCounter('10', 'info');
        $this->assertEquals('<span class="ms-1 badge rounded-pill text-bg-info">10</span>', $item->getCounter());
    }

    public function testGenerateTemplateWithExistingTemplate(): void
    {
        $item = new SidebarItem('tool', 'id', 'Label');
        $item->setTemplate('custom_template.html.twig');
        $item->generateTemplate();
        $this->assertEquals('custom_template.html.twig', $item->getTemplate());
    }
}
