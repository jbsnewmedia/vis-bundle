<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Twig;

use JBSNewMedia\VisBundle\Service\Vis;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Resolves theme aware template paths with graceful fallback to the
 * default theme ("vis") when the active theme does not ship the
 * requested template (e.g. a theme without its own simple pages).
 */
class VisThemeExtension extends AbstractExtension
{
    public function __construct(
        protected Environment $twig,
        protected Vis $vis,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vis_theme_template', $this->getThemeTemplate(...)),
        ];
    }

    /**
     * @param string $template relative template path inside a theme,
     *                         e.g. "tool/base.html.twig" or "simple/login.html.twig"
     */
    public function getThemeTemplate(string $template): string
    {
        if (!str_ends_with($template, '.html.twig')) {
            $template = $template.'/base.html.twig';
        }

        $theme = $this->vis->getTheme();

        $themeTemplate = sprintf('@Vis/themes/%s/%s', $theme, $template);
        if ($this->twig->getLoader()->exists($themeTemplate)) {
            return $themeTemplate;
        }

        return sprintf('@Vis/themes/%s/%s', Vis::DEFAULT_THEME, $template);
    }
}
