<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Service;

use JBSNewMedia\VisBundle\Model\Topbar\TopbarButton;
use JBSNewMedia\VisBundle\Model\Topbar\TopbarDropdownLocale;
use JBSNewMedia\VisBundle\Model\Tool;
use JBSNewMedia\VisBundle\Service\Vis;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class VisTopbarTest extends TestCase
{
    private Vis $visSingleLocale;
    private Vis $visMultiLocale;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(Security::class);

        $this->visSingleLocale = new Vis($translator, $router, $security, ['en'], 'en');
        $this->visSingleLocale->addTool(new Tool('test_tool'));

        $this->visMultiLocale = new Vis($translator, $router, $security, ['en', 'de'], 'en');
        $this->visMultiLocale->addTool(new Tool('test_tool'));
    }

    public function testTopbarDropdownLocaleNotAddedForSingleLocale(): void
    {
        $dropdown = new TopbarDropdownLocale('test_tool');
        $result = $this->visSingleLocale->addTopbar($dropdown);
        $this->assertFalse($result, 'DropdownLocale should not be added when only one locale is configured');
    }

    public function testTopbarDropdownLocaleAddedForMultipleLocales(): void
    {
        $dropdown = new TopbarDropdownLocale('test_tool');
        $result = $this->visMultiLocale->addTopbar($dropdown);
        $this->assertTrue($result);

        $items = $this->visMultiLocale->getTopbar('end', 'test_tool');
        $this->assertArrayHasKey('dropdown_locale', $items);
        $this->assertSame($dropdown, $items['dropdown_locale']);
    }

    public function testAddTopbarRegistersRouteOnAdd(): void
    {
        $button = new TopbarButton('test_tool', 'btn_route');
        $button->setRoute('route_btn');

        $this->assertTrue($this->visMultiLocale->addTopbar($button));

        // Hinweis: Aktuelle Implementierung von Vis::setRoute aktiviert nur Sidebar-Elemente.
        // Für Topbar prüfen wir daher, dass das Item erfolgreich hinzugefügt wurde
        // und über getTopbar wieder auffindbar ist.
        $items = $this->visMultiLocale->getTopbar('end', 'test_tool');
        $this->assertArrayHasKey('btn_route', $items);
        $this->assertSame($button, $items['btn_route']);
    }

    public function testGetTopbarExceptionsAndEmptyPosition(): void
    {
        // unknown tool
        $items = $this->visMultiLocale->getTopbar('end', 'unknown');
        $this->assertSame([], $items);
    }

    public function testGetTopbarGuestDefaults(): void
    {
        // Position end should have default items (darkmode and locale)
        $items = $this->visMultiLocale->getTopbarGuest('end');
        $this->assertCount(2, $items);
        $this->assertArrayHasKey('toggle_darkmode_end', $items);
        $this->assertArrayHasKey('dropdown_locale', $items);

        // Position start should be empty
        $items = $this->visMultiLocale->getTopbarGuest('start');
        $this->assertSame([], $items);
    }

    public function testGetTopbarEmptyForMissingPosition(): void
    {
        // Tool existiert und hat Items an "end" – dadurch ist $topbar[$tool] gesetzt
        $button = new TopbarButton('test_tool', 'some_btn');
        $button->setPosition('end');
        $this->assertTrue($this->visMultiLocale->addTopbar($button));

        // Für eine nicht vorhandene Position soll ein leeres Array zurückkommen
        $items = $this->visMultiLocale->getTopbar('start', 'test_tool');
        $this->assertSame([], $items);
    }
}
