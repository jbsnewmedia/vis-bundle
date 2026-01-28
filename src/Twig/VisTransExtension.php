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

    public function translateKey(string $var, array $parameters = [], ?string $domain = null): string
    {
        if (isset($this->cache[$var.'_'.($domain ?? 'null')])) {
            return $this->cache[$var.'_'.($domain ?? 'null')];
        }

        if (null === $domain) {
            $toolId = $this->vis->getToolId();
            if ('' !== $toolId) {
                $domain = 'vis_'.$toolId;
                if ($this->translator instanceof TranslatorBagInterface) {
                    if ($this->translator->getCatalogue()->has($var, $domain)) {
                        $trans = $this->translator->trans($var, $parameters, $domain);
                        $this->cache[$var.'_'.($domain ?? 'null')] = $trans;

                        return $trans;
                    }
                } else {
                    $trans = $this->translator->trans($var, $parameters, $domain);
                    if ($trans !== $var) {
                        $this->cache[$var.'_'.($domain ?? 'null')] = $trans;

                        return $trans;
                    }
                }
            }
            $domain = 'vis';
        }

        $trans = $this->translator->trans($var, $parameters, $domain);
        $this->cache[$var.'_'.($domain ?? 'null')] = $trans;

        return $trans;
    }
}
