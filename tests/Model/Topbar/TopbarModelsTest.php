<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model\Topbar;

use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdown;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButton;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButtonSidebar;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarButtonDarkmode;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearch;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearchTools;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdownLocale;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdownProfile;
use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Service\Vis;
use PHPUnit\Framework\TestCase;

class TopbarModelsTest extends TestCase
{
    public function testTopbarDropdown(): void
    {
        $dropdown = new TopbarDropdown('test_tool', 'dropdown_id');
        $this->assertEquals('dropdown', $dropdown->getType());

        $data = ['id1' => ['route' => 'r1', 'routeParameters' => [], 'icon' => 'i1', 'label' => 'L1']];
        $dropdown->setData($data);
        $this->assertEquals($data, $dropdown->getData());

        $dropdown->addData(['id2' => ['route' => 'r2', 'routeParameters' => [], 'icon' => 'i2', 'label' => 'L2']]);
        $this->assertCount(2, $dropdown->getData());

        $dropdown->setDataKey('key1');
        $this->assertEquals('key1', $dropdown->getDataKey());
    }

    public function testTopbarButton(): void
    {
        $button = new TopbarButton('test_tool', 'button_id');
        $this->assertEquals('button', $button->getType());
    }

    public function testTopbarButtonSidebar(): void
    {
        $button = new TopbarButtonSidebar('test_tool');
        $this->assertEquals('start', $button->getPosition());
        $this->assertStringContainsString('avalynx-simpleadmin-toggler-sidenav', $button->getClass());
        $this->assertStringContainsString('d-none d-md-flex', $button->getClass());
        $this->assertEquals('Toggle Sidebar', $button->getLabel());
        $this->assertEquals('raw', $button->getContentFilter());

        $buttonLarge = new TopbarButtonSidebar('test_tool', 'toggle', 'start', ['display' => 'large']);
        $this->assertStringContainsString('d-flex d-md-none', $buttonLarge->getClass());
    }

    public function testTopbarButtonDarkmode(): void
    {
        $button = new TopbarButtonDarkmode('test_tool');
        $this->assertEquals('end', $button->getPosition());
        $this->assertStringContainsString('avalynx-simpleadmin-toggler-darkmode', $button->getClass());
        $this->assertEquals('Toggle Darkmode', $button->getLabel());
        $this->assertEquals('@Vis/topbar/button_darkmode.html.twig', $button->getTemplate());
    }

    public function testTopbarLiveSearch(): void
    {
        $search = new TopbarLiveSearch('test_tool', 'search_id');
        $this->assertEquals('livesearch', $search->getType());
        $this->assertEquals('Search', $search->getLabelSearch());
        $this->assertEquals(10, $search->getCountForSearch());

        $data = ['id1' => ['route' => 'r1', 'routeParameters' => [], 'label' => 'L1']];
        $search->setData($data);
        $this->assertEquals($data, $search->getData());
        $this->assertEquals(1, $search->getDataCounter());

        $search->addData(['id2' => ['route' => 'r2', 'routeParameters' => [], 'label' => 'L2']]);
        $this->assertCount(2, $search->getData());
        $this->assertEquals(2, $search->getDataCounter());

        $search->setLabelSearch('New Search');
        $this->assertEquals('New Search', $search->getLabelSearch());

        $search->setCountForSearch(5);
        $this->assertEquals(5, $search->getCountForSearch());
    }

    public function testTopbarLiveSearchTools(): void
    {
        $vis = $this->createMock(Vis::class);
        $tool = new Tool('my_tool');
        $tool->setTitle('My Tool Title');

        $vis->method('getTools')->willReturn(['my_tool' => $tool]);
        $vis->method('getToolId')->willReturn('active_tool');

        $searchTools = new TopbarLiveSearchTools('test_tool');
        $searchTools->setVis($vis);

        $this->assertSame($vis, $searchTools->getVis());

        // This should trigger setVisData()
        $this->assertEquals(1, $searchTools->getDataCounter());
        $data = $searchTools->getData();
        $this->assertArrayHasKey('my_tool', $data);
        $this->assertEquals('My Tool Title', $data['my_tool']['label']);
        $this->assertEquals('active_tool', $searchTools->getDataKey());
    }

    public function testTopbarDropdownProfile(): void
    {
        $profile = new TopbarDropdownProfile('test_tool');
        $this->assertEquals('end', $profile->getPosition());
        $this->assertEquals('Profile', $profile->getLabel());
        $this->assertEquals('raw', $profile->getContentFilter());
        $this->assertEquals(100, $profile->getOrder());
    }

    public function testTopbarDropdownLocale(): void
    {
        // Vis mock for generateTemplate if needed, but generateTemplate uses file_exists.
        // TopbarDropdownLocale sets template explicitly.
        $locale = new TopbarDropdownLocale('test_tool');
        $this->assertEquals('dropdown_locale', $locale->getId());
        $this->assertEquals('end', $locale->getPosition());
        $this->assertEquals(90, $locale->getOrder());
        $this->assertEquals('@Vis/topbar/dropdown_locale.html.twig', $locale->getTemplate());
    }
}
