<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model\Sidebar;

use JBSNewMedia\VisBundle\Model\Sidebar\Sidebar;
use PHPUnit\Framework\TestCase;

class SidebarTemplateTest extends TestCase
{
    public function testGenerateTemplateSuccess(): void
    {
        $sidebar = new class('tool', 'id') extends Sidebar {
            public function getType(): string
            {
                return 'item';
            }
        };

        $sidebar->generateTemplate();
        $this->assertEquals('@Vis/sidebar/item.html.twig', $sidebar->getTemplate());
    }

    public function testGenerateTemplateFailure(): void
    {
        $sidebar = new class('tool', 'id') extends Sidebar {
            public function getType(): string
            {
                return 'non_existent_type';
            }
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Template not found: sidebar/non_existent_type.html.twig');
        $sidebar->generateTemplate();
    }

    public function testGenerateTemplateSkipsIfAlreadySet(): void
    {
        $sidebar = new class('tool', 'id') extends Sidebar {
            public function getType(): string
            {
                return 'item';
            }
        };

        $sidebar->setTemplate('custom_template.html.twig');
        $sidebar->generateTemplate();
        $this->assertEquals('custom_template.html.twig', $sidebar->getTemplate());
    }
}
