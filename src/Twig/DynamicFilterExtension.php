<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DynamicFilterExtension extends AbstractExtension
{
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
        $filter = $this->twig->getFilter($filterName);
        if (null === $filter) {
            throw new \RuntimeException(sprintf('Filter "%s" does not exist.', $filterName));
        }

        $callable = $filter->getCallable();
        if (!is_callable($callable)) {
            throw new \RuntimeException(sprintf('Filter "%s" is not callable.', $filterName));
        }

        return $callable($string);
    }
}
