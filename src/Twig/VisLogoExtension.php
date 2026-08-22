<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Twig;

use JBSNewMedia\AssetComposerBundle\Service\AssetComposer;
use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VisLogoExtension extends AbstractExtension
{
    public function __construct(
        protected UrlGeneratorInterface $router,
        protected Vis $vis,
        protected VisTransExtension $visTrans,
        protected AssetComposer $assetComposer
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vis_logo', $this->getLogo(...)),
            new TwigFunction('vis_logo_hover', $this->getLogoHover(...)),
        ];
    }

    public function getLogo(string $type, string $mode, ?string $fallback = null, ?string $fallbackKey = null): string
    {
        $toolId = $this->vis->getToolId();
        $url = null;

        if ($toolId !== '') {
            $routeName = sprintf('vis_tool_%s_logo_%s', $type, $mode);
            $url = $this->generateUrlSafe($routeName, ['tool' => $toolId]);
        }

        if (!$url) {
            $routeName = sprintf('vis_%s_logo_%s', $type, $mode);
            $url = $this->generateUrlSafe($routeName);
        }

        if ($url && $this->hasFile($type, $mode, $toolId)) {
            return $this->addVersion($url, $type, $mode, $toolId);
        }

        if ($type === 'brand') {
            if ($url) {
                return $url;
            }
            return '';
        }

        // 3. Fallback logic: If no route, try the provided fallbackKey or fallback string
        if ($fallbackKey !== null) {
            return $this->toAssetUrl($this->visTrans->translateKey($fallbackKey));
        }

        return $fallback ?? '';
    }

    /**
     * Resolves an asset composer path (e.g. "jbsnewmedia/vis-bundle/assets/img/logo.svg")
     * to a versioned URL. Returns the raw value when it cannot be resolved
     * (e.g. project relative "assets/img/..." paths).
     */
    protected function toAssetUrl(string $path): string
    {
        if ('' === $path || str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        try {
            return $this->assetComposer->getAssetFileName($path);
        } catch (\Throwable) {
            return $path;
        }
    }

    public function getLogoHover(string $type, string $mode): string
    {
        $toolId = $this->vis->getToolId();
        $url = null;

        // 1. Tool specific route: vis_[tool]_[type]_logo_hover
        if ($toolId !== '') {
            $routeName = sprintf('vis_tool_%s_logo_%s_hover', $type, $mode);
            $url = $this->generateUrlSafe($routeName, ['tool' => $toolId]);
        }

        // 2. Global route: vis_[type]_logo_hover
        if (!$url) {
            $routeName = sprintf('vis_%s_logo_%s_hover', $type, $mode);
            $url = $this->generateUrlSafe($routeName);
        }

        if ($url && $this->hasFile($type, $mode, $toolId, true)) {
            return $this->addVersion($url, $type, $mode, $toolId, true);
        }

        return '';
    }

    protected function hasFile(string $type, string $mode, string $toolId, bool $hover = false): bool
    {
        $filenames = $this->getFilenames($type, $mode, $toolId, $hover);

        foreach ($filenames as $filename) {
            $path = $this->vis->getProjectDir() . '/assets/img/' . $filename;
            if (file_exists($path)) {
                return true;
            }
        }

        return false;
    }

    protected function addVersion(string $url, string $type, string $mode, string $toolId, bool $hover = false): string
    {
        $filenames = $this->getFilenames($type, $mode, $toolId, $hover);

        foreach ($filenames as $filename) {
            $path = $this->vis->getProjectDir() . '/assets/img/' . $filename;
            if (file_exists($path)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($path);
                break;
            }
        }

        return $url;
    }

    protected function getFilenames(string $type, string $mode, string $toolId, bool $hover = false): array
    {
        $filenames = [];
        if ($toolId !== '') {
            $filenames[] = sprintf('%s_%s-%s%s.svg', $toolId, $type, $mode, $hover ? '-hover' : '');
            $filenames[] = sprintf('%s_%s%s.svg', $toolId, $type, $hover ? '-hover' : '');
        }

        $filenames[] = sprintf('%s-%s%s.svg', $type, $mode, $hover ? '-hover' : '');
        $filenames[] = sprintf('%s%s.svg', $type, $hover ? '-hover' : '');

        // Fallbacks for old filenames
        if ($toolId !== '') {
            $filenames[] = sprintf('%s_%s_logo_%s%s.svg', $toolId, $type, $mode, $hover ? '_hover' : '');
            $filenames[] = sprintf('%s_%s_logo%s.svg', $toolId, $type, $hover ? '_hover' : '');
        }
        $filenames[] = sprintf('%s_logo_%s%s.svg', $type, $mode, $hover ? '_hover' : '');
        $filenames[] = sprintf('%s_logo%s.svg', $type, $hover ? '_hover' : '');

        return $filenames;
    }

    protected function generateUrlSafe(string $routeName, array $params = []): ?string
    {
        try {
            return $this->router->generate($routeName, array_merge(['ext' => 'svg'], $params));
        } catch (RouteNotFoundException) {
            return null;
        }
    }
}
