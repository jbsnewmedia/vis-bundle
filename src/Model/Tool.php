<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model;

class Tool
{
    use \JBSNewMedia\VisBundle\Trait\RolesTrait;

    protected string $title = '';

    public function __construct(protected string $id)
    {
        $this->setId($id);
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
