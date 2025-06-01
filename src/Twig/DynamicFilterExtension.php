<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DynamicFilterExtension extends AbstractExtension
{
    /**
     * @var array<string, TwigFilter|null>
     */
    private array $filter = [];

    public function __construct(private readonly Environment $twig)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('dynamic_filter', $this->dynamicFilter(...), ['is_safe' => ['html']]),
        ];
    }

    public function dynamicFilter(string $string, string $filterName): string
    {
        if ('raw' === $filterName) {
            return $string;
        }

        if (!isset($this->filter[$filterName])) {
            $this->filter[$filterName] = $this->twig->getFilter($filterName);
        }

        if (null === $this->filter[$filterName]) {
            throw new \RuntimeException(sprintf('Filter "%s" does not exist.', $filterName));
        }

        $callable = $this->filter[$filterName]->getCallable();
        if (!is_callable($callable)) {
            throw new \RuntimeException(sprintf('Filter "%s" is not callable.', $filterName));
        }

        return $callable($string);
    }
}
