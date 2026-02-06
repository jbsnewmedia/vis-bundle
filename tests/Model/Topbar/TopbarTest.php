<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model\Topbar;

use JBSNewMedia\VisBundle\Model\Topbar\Topbar;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdown;
use PHPUnit\Framework\TestCase;

class TopbarTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $topbar = new Topbar('test_tool', 'test_id');
        $this->assertEquals('test_tool', $topbar->getTool());
        $this->assertEquals('test_id', $topbar->getId());
        $this->assertEquals('end', $topbar->getPosition());
    }

    public function testPosition(): void
    {
        $topbar = new Topbar('test_tool', 'test_id');
        $topbar->setPosition('start');
        $this->assertEquals('start', $topbar->getPosition());
    }

    public function testContent(): void
    {
        $topbar = new Topbar('test_tool', 'test_id');
        $topbar->setContent('some content');
        $this->assertEquals('some content', $topbar->getContent());
        $topbar->setContentFilter('raw');
        $this->assertEquals('raw', $topbar->getContentFilter());
    }

    public function testGenerateTemplateThrowsExceptionWhenFileNotFound(): void
    {
        $topbar = new Topbar('test_tool', 'id');
        $topbar->setType('non_existent_type');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Template not found: topbar/non_existent_type.html.twig');
        $topbar->generateTemplate();
    }
}

class TopbarDropdownTest extends TestCase
{
    public function testConstructor(): void
    {
        $dropdown = new TopbarDropdown('test_tool', 'dropdown_id');
        $this->assertEquals('dropdown', $dropdown->getType());
        $this->assertEquals('@Vis/topbar/dropdown.html.twig', $dropdown->getTemplate());
    }

    public function testData(): void
    {
        $dropdown = new TopbarDropdown('test_tool', 'dropdown_id');
        $data = [
            'item1' => [
                'route' => 'home',
                'routeParameters' => [],
                'icon' => 'fa fa-home',
                'label' => 'Home'
            ]
        ];
        $dropdown->setData($data);
        $this->assertEquals($data, $dropdown->getData());

        $moreData = [
            'item2' => [
                'route' => 'settings',
                'routeParameters' => [],
                'icon' => 'fa fa-cog',
                'label' => 'Settings'
            ]
        ];
        $dropdown->addData($moreData);
        $this->assertCount(2, $dropdown->getData());
        $this->assertArrayHasKey('item2', $dropdown->getData());
    }

    public function testDataKey(): void
    {
        $dropdown = new TopbarDropdown('test_tool', 'dropdown_id');
        $dropdown->setDataKey('user_menu');
        $this->assertEquals('user_menu', $dropdown->getDataKey());
    }
}
