<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model\Sidebar;

use JBSNewMedia\VisBundle\Model\Sidebar\SidebarHeader;
use PHPUnit\Framework\TestCase;

class SidebarHeaderTest extends TestCase
{
    public function testConstructor(): void
    {
        $header = new SidebarHeader('tool', 'id', 'My Header');
        $this->assertEquals('header', $header->getType());
        $this->assertEquals('My Header', $header->getLabel());
        $this->assertEquals('@Vis/sidebar/header.html.twig', $header->getTemplate());
    }
}
