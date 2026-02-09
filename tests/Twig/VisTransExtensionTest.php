<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Twig;

use JBSNewMedia\VisBundle\Service\Vis;
use JBSNewMedia\VisBundle\Twig\VisTransExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;

class VisTransExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $vis = $this->createMock(Vis::class);
        $extension = new VisTransExtension($translator, $vis);
        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertEquals('vistrans', $filters[0]->getName());
    }

    public function testTranslateKeyWithDomain(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $vis = $this->createMock(Vis::class);

        $translator->expects($this->once())
            ->method('trans')
            ->with('my_key', ['%param%' => 'value'], 'my_domain')
            ->willReturn('translated_value');

        $extension = new VisTransExtension($translator, $vis);
        $result = $extension->translateKey('my_key', ['%param%' => 'value'], 'my_domain');

        $this->assertEquals('translated_value', $result);

        // Test cache
        $resultCache = $extension->translateKey('my_key', ['%param%' => 'value'], 'my_domain');
        $this->assertEquals('translated_value', $resultCache);
    }

    public function testTranslateKeyWithoutDomainToolFound(): void
    {
        // Mock TranslatorBagInterface
        $translator = $this->createMock(TestTranslatorBag::class);
        $vis = $this->createMock(Vis::class);
        $catalogue = $this->createMock(MessageCatalogueInterface::class);

        $vis->method('getToolId')->willReturn('my_tool');

        $translator->method('getCatalogue')->willReturn($catalogue);
        $catalogue->method('has')->with('my_key', 'vis_my_tool')->willReturn(true);

        $translator->expects($this->once())
            ->method('trans')
            ->with('my_key', [], 'vis_my_tool')
            ->willReturn('tool_translated');

        $extension = new VisTransExtension($translator, $vis);
        $result = $extension->translateKey('my_key');

        $this->assertEquals('tool_translated', $result);
    }

    public function testTranslateKeyWithoutDomainDefaultFallback(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $vis = $this->createMock(Vis::class);

        $vis->method('getToolId')->willReturn('my_tool');

        // Simulate no translation found for tool domain (trans returns the key)
        $translator->method('trans')
            ->willReturnMap([
                ['my_key', [], 'vis_my_tool', null, 'my_key'],
                ['my_key', [], 'vis', null, 'default_translated']
            ]);

        $extension = new VisTransExtension($translator, $vis);
        $result = $extension->translateKey('my_key');

        $this->assertEquals('default_translated', $result);
    }

    public function testTranslateKeyWithToolIdButNoTranslatorBag(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $vis = $this->createMock(Vis::class);

        $vis->method('getToolId')->willReturn('my_tool');

        $translator->expects($this->once())
            ->method('trans')
            ->with('my_key', [], 'vis_my_tool')
            ->willReturn('translated_by_fallback');

        $extension = new VisTransExtension($translator, $vis);
        $result = $extension->translateKey('my_key');

        $this->assertEquals('translated_by_fallback', $result);
    }
}

// Helper interface for mocking
interface TestTranslatorBag extends TranslatorInterface, TranslatorBagInterface {}
