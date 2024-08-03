<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Plugin;

abstract class AbstractPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function init(): void
    {
    }

    public function setNavigation(): void
    {
    }

    public function setTopBar(): void
    {
    }
}
