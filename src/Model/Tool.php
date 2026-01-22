<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model;

class Tool
{
    use \JBSNewMedia\VisBundle\Trait\RolesTrait;

    protected string $title = '';
    protected bool $merge = false;

    public function __construct(protected string $id, protected int $priority = 100)
    {
        $this->setId($id);
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setMerge(bool $merge): self
    {
        $this->merge = $merge;

        return $this;
    }

    public function isMerge(): bool
    {
        return $this->merge;
    }
}
