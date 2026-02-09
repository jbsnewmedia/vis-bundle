<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Twig;

use JBSNewMedia\VisBundle\Twig\DynamicFilterExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\TwigFilter;

class DynamicFilterExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $twig = $this->createMock(Environment::class);
        $extension = new DynamicFilterExtension($twig);
        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('dynamic_filter', $filters[0]->getName());
    }

    public function testDynamicFilterRaw(): void
    {
        $twig = $this->createMock(Environment::class);
        $extension = new DynamicFilterExtension($twig);

        $result = $extension->dynamicFilter('<b>test</b>', 'raw');
        $this->assertEquals('<b>test</b>', $result);
    }

    public function testDynamicFilterCallable(): void
    {
        $twig = $this->createMock(Environment::class);
        $filter = new TwigFilter('upper', 'strtoupper');

        $twig->method('getFilter')->with('upper')->willReturn($filter);

        $extension = new DynamicFilterExtension($twig);
        $result = $extension->dynamicFilter('test', 'upper');
        $this->assertEquals('TEST', $result);
    }

    public function testDynamicFilterNonExistent(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('getFilter')->willReturn(null);

        $extension = new DynamicFilterExtension($twig);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Filter "unknown" does not exist.');
        $extension->dynamicFilter('test', 'unknown');
    }

    public function testDynamicFilterNotCallable(): void
    {
        $twig = $this->createMock(Environment::class);
        $filter = new TwigFilter('invalid', null);

        $twig->method('getFilter')->willReturn($filter);

        $extension = new DynamicFilterExtension($twig);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Filter "invalid" is not callable.');
        $extension->dynamicFilter('test', 'invalid');
    }

    public function testDynamicFilterWithParameters(): void
    {
        $twig = $this->createMock(Environment::class);

        // strtoupper is a good candidate for a filter without extra parameters
        $filter = new TwigFilter('upper', 'strtoupper');
        $twig->method('getFilter')->with('upper')->willReturn($filter);

        $extension = new DynamicFilterExtension($twig);

        $result = $extension->dynamicFilter('a', 'upper');
        $this->assertEquals('A', $result);
    }

    public function testDynamicFilterReturnsScalarNonString(): void
    {
        $twig = $this->createMock(Environment::class);

        // Filter that returns an integer (scalar but not string)
        $filter = new TwigFilter('strlen', 'strlen');
        $twig->method('getFilter')->with('strlen')->willReturn($filter);

        $extension = new DynamicFilterExtension($twig);

        $result = $extension->dynamicFilter('hello', 'strlen');
        $this->assertEquals('5', $result);
    }

    public function testDynamicFilterReturnsNonScalar(): void
    {
        $twig = $this->createMock(Environment::class);

        // Filter that returns an array (non-scalar)
        $filter = new TwigFilter('array_return', fn (string $s): array => [$s]);
        $twig->method('getFilter')->with('array_return')->willReturn($filter);

        $extension = new DynamicFilterExtension($twig);

        $result = $extension->dynamicFilter('test', 'array_return');
        $this->assertEquals('', $result);
    }
}
