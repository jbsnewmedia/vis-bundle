<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model\Topbar;

use JBSNewMedia\VisBundle\Model\Topbar\Topbar;
use PHPUnit\Framework\TestCase;

class TopbarTemplateTest extends TestCase
{
    public function testGenerateTemplateSuccess(): void
    {
        $topbar = new class('tool', 'id') extends Topbar {
            public function getType(): string
            {
                return 'button';
            }
        };

        $topbar->generateTemplate();
        $this->assertEquals('@Vis/topbar/button.html.twig', $topbar->getTemplate());
    }

    public function testGenerateTemplateFailure(): void
    {
        $topbar = new class('tool', 'id') extends Topbar {
            public function getType(): string
            {
                return 'non_existent_type';
            }
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Template not found: topbar/non_existent_type.html.twig');
        $topbar->generateTemplate();
    }

    public function testGenerateTemplateSkipsIfAlreadySet(): void
    {
        $topbar = new class('tool', 'id') extends Topbar {
            public function getType(): string
            {
                return 'button';
            }
        };

        $topbar->setTemplate('custom_template.html.twig');
        $topbar->generateTemplate();
        $this->assertEquals('custom_template.html.twig', $topbar->getTemplate());
    }
}
