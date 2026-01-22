<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Twig;

use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class VisTransExtension extends AbstractExtension
{
    /**
     * @var array <string, string>
     */
    protected array $cache = [];

    public function __construct(protected TranslatorInterface $translator, protected Vis $vis)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('vistrans', $this->translateKey(...)),
        ];
    }

    public function translateKey(string $var): string
    {
        if (isset($this->cache[$var])) {
            return $this->cache[$var];
        }

        $toolId = $this->vis->getToolId();
        if ('' !== $toolId) {
            $domain = 'vis_'.$toolId;
            if ($this->translator instanceof TranslatorBagInterface) {
                if ($this->translator->getCatalogue()->has($var, $domain)) {
                    $trans = $this->translator->trans($var, [], $domain);
                    $this->cache[$var] = $trans;

                    return $trans;
                }
            } else {
                $trans = $this->translator->trans($var, [], $domain);
                if ($trans !== $var) {
                    $this->cache[$var] = $trans;

                    return $trans;
                }
            }
        }

        $trans = $this->translator->trans($var, [], 'vis');
        $this->cache[$var] = $trans;

        return $trans;
    }
}
