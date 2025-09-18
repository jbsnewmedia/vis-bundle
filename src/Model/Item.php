<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model;

class Item
{
    use \JBSNewMedia\VisBundle\Trait\RolesTrait;

    protected string $type = '';

    protected bool $merge = false;

    protected int $order = 0;

    protected bool $active = false;

    protected string $template = '';

    protected string $class = '';

    protected string $onClick = '';

    protected string $label = '';

    protected string $route = '';

    /**
     * @var array<string, string|int>
     */
    protected array $routeParameters = [];

    public \Closure $callbackFunction;

    public function __construct(protected string $tool, protected string $id)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tool)) {
            throw new \InvalidArgumentException('Vis: Tool name must only contain letters, numbers and underscores "'.$tool.'"');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $id)) {
            throw new \InvalidArgumentException('Vis: Item name must only contain letters, numbers and underscores "'.$id.'"');
        }
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTool(string $tool): self
    {
        $this->tool = $tool;

        return $this;
    }

    public function getTool(): string
    {
        return $this->tool;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setOnClick(string $onClick): self
    {
        $this->onClick = $onClick;

        return $this;
    }

    public function getOnClick(): string
    {
        return $this->onClick;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @param array<string, string|int> $routeParameters
     */
    public function setRouteParameters(array $routeParameters): self
    {
        $this->routeParameters = $routeParameters;

        return $this;
    }

    /**
     * @return array<string, string|int>
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function setCallbackFunction(\Closure $callbackFunction): self
    {
        $this->callbackFunction = $callbackFunction;

        return $this;
    }

    public function getCallbackFunction(): ?\Closure
    {
        if (!isset($this->callbackFunction)) {
            return null;
        }

        return $this->callbackFunction;
    }
}
