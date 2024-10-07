<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Twig;

use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class VisTransExtension extends AbstractExtension
{

    /**
     * @var array <string, string> $cache
     */
    protected array $cache = [];

    public function __construct(protected TranslatorInterface $translator, protected Vis $vis)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('vistrans', [$this, 'translateKey']),
        ];
    }

    public function translateKey(string $var): string
    {
        if (isset($this->cache[$var])) {
            return $this->cache[$var];
        }

        $trans = $this->translator->trans($var, [], 'vis_' . $this->vis->getToolId());
        if ($trans === $var) {
            $trans = $this->translator->trans($var, [], 'vis');
        }

        $this->cache[$var] = $trans;

        return $trans;
    }
}
