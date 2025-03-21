<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model;

class Item
{
    use \JBSNewMedia\VisBundle\Trait\RolesTrait;

    protected string $type = '';

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

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTool(string $tool): void
    {
        $this->tool = $tool;
    }

    public function getTool(): string
    {
        return $this->tool;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setOnClick(string $onClick): void
    {
        $this->onClick = $onClick;
    }

    public function getOnClick(): string
    {
        return $this->onClick;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setRoute(string $route): void
    {
        $this->route = $route;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @param array<string, string|int> $routeParameters
     */
    public function setRouteParameters(array $routeParameters): void
    {
        $this->routeParameters = $routeParameters;
    }

    /**
     * @return array<string, string|int>
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function setCallbackFunction(\Closure $callbackFunction): void
    {
        $this->callbackFunction = $callbackFunction;
    }

    public function getCallbackFunction(): ?\Closure
    {
        if (!isset($this->callbackFunction)) {
            return null;
        }

        return $this->callbackFunction;
    }
}
