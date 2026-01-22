<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Plugin;

interface PluginInterface
{
    public function getPluginId(): ?string;

    public function init(): void;

    public function setNavigation(): void;

    public function setTopBar(): void;
}
