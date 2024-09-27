<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Topbar;

use JBSNewMedia\VisBundle\Entity\Item;

class Topbar extends Item
{
    protected string $position = 'end';

    protected string $content = '';

    protected string $contentFilter = '';

    public function __construct(
        string $tool,
        string $id,
    ) {
        parent::__construct($tool, $id);
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContentFilter(string $contentFilter): void
    {
        $this->contentFilter = $contentFilter;
    }

    public function getContentFilter(): string
    {
        return $this->contentFilter;
    }

    public function generateTemplate(): void
    {
        if ('' === $this->getTemplate()) {
            if (file_exists(__DIR__.'/../../../templates/topbar/'.$this->getType().'.html.twig')) {
                $this->setTemplate('@VisBundle/topbar/'.$this->getType().'.html.twig');
            } else {
                throw new \Exception('Template not found: topbar/'.$this->getType().'.html.twig');
            }
        }
    }
}
