<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model\Sidebar;

use JBSNewMedia\VisBundle\Model\Sidebar\Sidebar;
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarItem;
use PHPUnit\Framework\TestCase;

class SidebarTest extends TestCase
{
    public function testAddAndGetChild(): void
    {
        $sidebar = new Sidebar('tool', 'parent_id');
        $child = new SidebarItem('tool', 'child_id', 'Child Label');

        $sidebar->addChild($child);

        $this->assertTrue($sidebar->isChild('child_id'));
        $this->assertSame($child, $sidebar->getChild('child_id'));
        $this->assertCount(1, $sidebar->getChildren());
        $this->assertSame(['child_id' => $child], $sidebar->getChildren());
    }

    public function testGetChildNotFound(): void
    {
        $sidebar = new Sidebar('tool', 'parent_id');
        $this->expectException(\InvalidArgumentException::class);
        $sidebar->getChild('non_existent');
    }

    public function testSetParentSetsCallback(): void
    {
        $sidebar = new Sidebar('tool', 'id');
        $this->assertNull($sidebar->getCallbackFunction());

        $sidebar->setParent('another_parent');
        $this->assertEquals('another_parent', $sidebar->getParent());
        $this->assertNotNull($sidebar->getCallbackFunction());
    }

    public function testSetChildren(): void
    {
        $sidebar = new Sidebar('tool', 'id');
        $child1 = new SidebarItem('tool', 'c1', 'L1');
        $child2 = new SidebarItem('tool', 'c2', 'L2');

        $children = ['c1' => $child1, 'c2' => $child2];
        $sidebar->setChildren($children);

        $this->assertSame($children, $sidebar->getChildren());
    }

    public function testGenerateTemplateThrowsExceptionWhenFileNotFound(): void
    {
        $sidebar = new Sidebar('tool', 'id');
        $sidebar->setType('non_existent_type');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Template not found: sidebar/non_existent_type.html.twig');
        $sidebar->generateTemplate();
    }

    public function testSetParentCallbackExceptionParentNotFound(): void
    {
        $vis = $this->createMock(\JBSNewMedia\VisBundle\Service\Vis::class);
        $vis->method('getSidebar')->willReturn(['existing' => new Sidebar('tool', 'existing')]);

        $item = new SidebarItem('tool', 'id', 'Label');
        $item->setParent('non_existent');
        $callback = $item->getCallbackFunction();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vis: Sidebar parent "non_existent" does not exist');
        $callback($vis, $item);
    }

    public function testSetParentCallbackExceptionChildNotFound(): void
    {
        $vis = $this->createMock(\JBSNewMedia\VisBundle\Service\Vis::class);
        $parent = new Sidebar('tool', 'p1');
        $vis->method('getSidebar')->willReturn(['p1' => $parent]);

        $item = new SidebarItem('tool', 'id', 'Label');
        $item->setParent('p1-p2'); // p1 exists, but p2 is not a child of p1
        $callback = $item->getCallbackFunction();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vis: Sidebar parent "p2" does not exist');
        $callback($vis, $item);
    }
}
