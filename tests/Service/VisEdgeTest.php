<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Service;

use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Model\Sidebar\Sidebar;
use JBSNewMedia\VisBundle\Model\Sidebar\SidebarItem;
use JBSNewMedia\VisBundle\Service\Vis;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class VisEdgeTest extends TestCase
{
    private $translator;
    private $router;
    private $security;
    private Vis $vis;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->security = $this->createMock(Security::class);

        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER', 'ROLE_ADMIN']);
        $this->security->method('getUser')->willReturn($user);

        $this->vis = new Vis(
            $this->translator,
            $this->router,
            $this->security,
            ['en', 'de'],
            'en'
        );
    }

    public function testAddToolMergeRoles(): void
    {
        $tool1 = new Tool('test_tool', 10);
        $tool1->addRole('ROLE_USER');
        $this->vis->addTool($tool1);

        $tool2 = new Tool('test_tool', 20); // Higher priority
        $tool2->setMerge(true);
        $tool2->addRole('ROLE_ADMIN');
        $tool2->addRole('ROLE_SUPER_ADMIN');

        $this->vis->addTool($tool2);

        $tools = $this->vis->getTools();
        $this->assertEquals(20, $tools['test_tool']->getPriority());
        $this->assertContains('ROLE_USER', $tools['test_tool']->getRoles());
        $this->assertContains('ROLE_ADMIN', $tools['test_tool']->getRoles());
        $this->assertContains('ROLE_SUPER_ADMIN', $tools['test_tool']->getRoles());
    }

    public function testAddSidebarRoleFiltering(): void
    {
        $this->vis->addTool(new Tool('test_tool'));

        // Item with role the user doesn't have
        $item = new Sidebar('test_tool', 'restricted');
        $item->setRoles(['ROLE_MANAGER']);

        $this->assertFalse($this->vis->addSidebar($item));

        // Ensure it's not in sidebar. getSidebar throws exception if tool has no sidebar entries yet
        try {
            $sidebar = $this->vis->getSidebar('test_tool');
            $this->assertArrayNotHasKey('restricted', $sidebar);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Vis: Tool "test_tool" does not exist in sidebar', $e->getMessage());
        }
    }

    public function testAddSidebarDeepNestingCallback(): void
    {
        $this->vis->addTool(new Tool('test_tool'));

        $gp = new Sidebar('test_tool', 'gp');
        $this->vis->addSidebar($gp);

        $p = new SidebarItem('test_tool', 'p', 'Parent');
        $p->setParent('gp');
        $this->vis->addSidebar($p);

        $c = new SidebarItem('test_tool', 'c', 'Child');
        $c->setParent('gp-p'); // Nested ID
        $this->assertTrue($this->vis->addSidebar($c));

        $sidebar = $this->vis->getSidebar('test_tool');
        $this->assertTrue($sidebar['gp']->getChild('p')->isChild('c'));
    }

    public function testSetRouteComplexNesting(): void
    {
        $this->vis->addTool(new Tool('test_tool'));

        $l1 = new Sidebar('test_tool', 'l1');
        $l1->setRoute('l1');
        $this->vis->addSidebar($l1);

        $l2 = new SidebarItem('test_tool', 'l2', 'L2');
        $l2->setParent('l1');
        $l2->setRoute('l1-l2');
        $this->vis->addSidebar($l2);

        $l3 = new SidebarItem('test_tool', 'l3', 'L3');
        $l3->setParent('l1-l2');
        $l3->setRoute('l1-l2-l3');
        $this->vis->addSidebar($l3);

        $this->vis->setRoute('test_tool', 'l1-l2-l3');

        $this->assertTrue($l1->getActive());
        $this->assertTrue($l2->getActive());
        $this->assertTrue($l3->getActive());
    }

    public function testAddSidebarConflictingParentException(): void
    {
        $this->vis->addTool(new Tool('test_tool'));
        $item = new Sidebar('test_tool', 'item');
        $item->setParent('parent1');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vis: Conflicting sidebar parent provided');

        $this->vis->addSidebar($item, 'parent2');
    }
}
