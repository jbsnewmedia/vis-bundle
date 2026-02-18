<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Service;

use JBSNewMedia\VisBundle\Model\Sidebar\Sidebar;
use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Model\Topbar\Topbar;
use JBSNewMedia\VisBundle\Service\Vis;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class VisCoverageTest extends TestCase
{
    private $translator;
    private $router;
    private $security;
    private $vis;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->security = $this->createMock(Security::class);

        $this->vis = new Vis(
            $this->translator,
            $this->router,
            $this->security,
            ['en', 'de'],
            'en'
        );
    }

    public function testAddTopbarMissingTool(): void
    {
        $topbar = new Topbar('non_existent', 'id');
        $this->assertTrue($this->vis->addTopbar($topbar));
    }

    public function testGetTopbarMissingTool(): void
    {
        $this->assertEquals([], $this->vis->getTopbar('end', 'non_existent'));
    }

    public function testGetTopbarMissingTopbarArray(): void
    {
        $this->vis->addTool(new Tool('my_tool'));
        $this->assertEquals([], $this->vis->getTopbar('end', 'my_tool'));
    }

    public function testGetSidebarThrowsExceptionIfToolNotInSidebarArray(): void
    {
        $this->vis->addTool(new Tool('my_tool'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vis: Tool "my_tool" does not exist in sidebar');
        $this->vis->getSidebar('my_tool');
    }

    public function testAddSidebarMissingTool(): void
    {
        $sidebar = new Sidebar('non_existent', 'id');
        $this->vis->addRole('ROLE_USER');
        $this->assertTrue($this->vis->addSidebar($sidebar));
    }

    public function testSetRouteThrowsExceptionIfToolDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // Da die Prüfung auf Tool-Existenz in setRoute indirekt über $this->routes erfolgt:
        $this->vis->setRoute('non_existent', 'route');
    }

    public function testSetRouteChildNotSidebarItem(): void
    {
        $this->vis->addRole('ROLE_USER');
        $this->vis->addTool(new Tool('tool'));

        // gp (Sidebar) -> p (SidebarItem)
        $gp = new Sidebar('tool', 'gp');
        $gp->setRoute('gp');
        $this->vis->addSidebar($gp);

        // p ist ein SidebarItem, aber wir brauchen eine Situation,
        // in der getChild() in der Schleife aufgerufen wird.

        $p = new \JBSNewMedia\VisBundle\Model\Sidebar\SidebarItem('tool', 'p', 'Label');
        $p->setParent('gp');
        $p->setRoute('gp-p');
        $this->vis->addSidebar($p);

        // gp-p-c -> gp ist routes[0], p ist routes[1], c ist routes[2]
        // Schleife läuft für $i=1 (routes[1]='p') und $i=2 (routes[2]='c')
        // Bei $i=1: $child ist $gp, $child->isChild('p') ist true, $child->getChild('p') liefert $p. $child wird $p.
        // Bei $i=2: $child ist $p, $child->isChild('c') ist false -> Exception.

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vis: Sidebar parent "c" does not exist');
        $this->vis->setRoute('tool', 'gp-p-c');
    }
}
