<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model;

use JBSNewMedia\VisBundle\Model\Item;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    public function testConstructor(): void
    {
        $item = new Item('my_tool', 'my_id');
        $this->assertEquals('my_tool', $item->getTool());
        $this->assertEquals('my_id', $item->getId());
    }

    public function testConstructorInvalidTool(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Item('invalid-tool!', 'id');
    }

    public function testConstructorInvalidId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Item('tool', 'invalid-id!');
    }

    public function testSettersAndGetters(): void
    {
        $item = new Item('tool', 'id');

        $item->setId('new_id');
        $this->assertEquals('new_id', $item->getId());

        $item->setType('my_type');
        $this->assertEquals('my_type', $item->getType());

        $item->setMerge(true);
        $this->assertTrue($item->isMerge());

        $item->setOrder(10);
        $this->assertEquals(10, $item->getOrder());

        $item->setActive(true);
        $this->assertTrue($item->getActive());

        $item->setTemplate('template.html.twig');
        $this->assertEquals('template.html.twig', $item->getTemplate());

        $item->setTool('new_tool');
        $this->assertEquals('new_tool', $item->getTool());

        $item->setClass('my-class');
        $this->assertEquals('my-class', $item->getClass());

        $item->setOnClick('alert("hello")');
        $this->assertEquals('alert("hello")', $item->getOnClick());

        $item->setLabel('My Label');
        $this->assertEquals('My Label', $item->getLabel());

        $item->setRoute('my_route');
        $this->assertEquals('my_route', $item->getRoute());

        $params = ['id' => 1];
        $item->setRouteParameters($params);
        $this->assertEquals($params, $item->getRouteParameters());

        $callback = function() { return 'hello'; };
        $item->setCallbackFunction($callback);
        $this->assertSame($callback, $item->getCallbackFunction());
    }

    public function testGetCallbackFunctionNotSet(): void
    {
        $item = new Item('tool', 'id');
        $this->assertNull($item->getCallbackFunction());
    }
}
