<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity;

class Tool
{
    protected string $title = '';

    public function __construct(protected string $id)
    {
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
