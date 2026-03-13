<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model\Topbar;

use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearch;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearchTools;
use JBSNewMedia\VisBundle\Service\Vis;
use PHPUnit\Framework\TestCase;

class TopbarLiveSearchTest extends TestCase
{
    public function testConstructor(): void
    {
        $search = new TopbarLiveSearch('test_tool', 'search_id');
        $this->assertEquals('livesearch', $search->getType());
        $this->assertEquals('Search', $search->getLabelSearch());
        $this->assertEquals(10, $search->getCountForSearch());
    }

    public function testData(): void
    {
        $search = new TopbarLiveSearch('test_tool', 'search_id');
        $data = [
            'tool1' => [
                'route' => 'vis_tool1',
                'routeparameters' => [],
                'label' => 'Tool 1'
            ]
        ];
        $search->setData($data);
        $this->assertEquals($data, $search->getData());
        $this->assertEquals(1, $search->getDataCounter());

        $search->addData(['tool2' => ['route' => 'vis_tool2', 'routeparameters' => [], 'label' => 'Tool 2']]);
        $this->assertCount(2, $search->getData());
        $this->assertEquals(2, $search->getDataCounter());
    }

    public function testSettersAndGetters(): void
    {
        $search = new TopbarLiveSearch('test_tool', 'search_id');
        $search->setLabelSearch('Suchen');
        $this->assertEquals('Suchen', $search->getLabelSearch());

        $search->setCountForSearch(5);
        $this->assertEquals(5, $search->getCountForSearch());

        $search->setDataKey('my_key');
        $this->assertEquals('my_key', $search->getDataKey());
    }
}

class TopbarLiveSearchToolsTest extends TestCase
{
    public function testConstructor(): void
    {
        $toolsSearch = new TopbarLiveSearchTools('test_tool');
        $this->assertEquals('dropdown_tools_end', $toolsSearch->getId());
        $this->assertEquals('end', $toolsSearch->getPosition());
        $this->assertEquals(100, $toolsSearch->getOrder());
    }

    public function testSetVisAndDataLoading(): void
    {
        $vis = $this->createMock(Vis::class);
        $tool1 = new Tool('tool1');
        $tool1->setTitle('Title 1');
        $tool2 = new Tool('tool2');
        $tool2->setTitle('Title 2');

        $vis->method('getTools')->willReturn(['tool1' => $tool1, 'tool2' => $tool2]);
        $vis->method('getToolId')->willReturn('active_tool');

        $toolsSearch = new TopbarLiveSearchTools('test_tool');
        $toolsSearch->setVis($vis);
        $this->assertSame($vis, $toolsSearch->getVis());

        // getDataCounter triggers setVisData
        $count = $toolsSearch->getDataCounter();
        $this->assertEquals(2, $count);
        $this->assertEquals('active_tool', $toolsSearch->getDataKey());

        $data = $toolsSearch->getData();
        $this->assertArrayHasKey('tool1', $data);
        $this->assertEquals('vis_tool1', $data['tool1']['route']);
        $this->assertEquals('Title 1', $data['tool1']['label']);
    }
}

class TopbarLiveSearchClientsTest extends TestCase
{
    public function testConstructor(): void
    {
        $clientsSearch = new \JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearchClients('test_tool');
        $this->assertEquals('dropdown_clients_end', $clientsSearch->getId());
        $this->assertEquals('end', $clientsSearch->getPosition());
        $this->assertEquals(90, $clientsSearch->getOrder());
    }

    public function testSetVisAndDataLoading(): void
    {
        $vis = $this->createMock(Vis::class);
        $vis->method('getClients')->willReturn(['id1' => 'Client 1', 'id2' => 'Client 2']);
        $vis->method('getSelectedClientId')->willReturn('id1');
        $vis->method('getSelectedClientTitle')->willReturn('Client 1');
        $translator = $this->createMock(\Symfony\Contracts\Translation\TranslatorInterface::class);
        $vis->method('getTranslator')->willReturn($translator);

        $clientsSearch = new \JBSNewMedia\VisBundle\Model\Topbar\TopbarLiveSearchClients('test_tool');
        $clientsSearch->setVis($vis);
        $this->assertSame($vis, $clientsSearch->getVis());

        // getDataCounter triggers setVisData
        $count = $clientsSearch->getDataCounter();
        $this->assertEquals(2, $count);

        $data = $clientsSearch->getData();
        $this->assertArrayHasKey('id1', $data);
        $this->assertEquals('vis_api_client', $data['id1']['route']);
        $this->assertEquals(['id' => 'id1'], $data['id1']['routeparameters']);
        $this->assertEquals('Client 1', $data['id1']['label']);

        $content = $clientsSearch->getContent();
        $this->assertEquals('<i class="fa-solid fa-building fa-fw"></i>', $content);

        $label = $clientsSearch->getLabel();
        $this->assertEquals('Client 1', $label);
    }
}
