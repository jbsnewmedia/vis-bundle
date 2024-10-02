<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Topbar;

class TopbarDropdown extends Topbar
{
    /**
     * @var array<string, array{route: string, routeParameters: array<string, string|int>, icon: string, label: string}>
     */
    protected array $data = [];

    protected string $dataKey = '';

    public function __construct(
        string $tool,
        string $id,
    ) {
        parent::__construct($tool, $id);
        $this->setType('dropdown');
        $this->generateTemplate();
    }

    /**
     * @param array<string, array{route: string, routeParameters: array<string, string|int>, icon: string, label: string}> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param array<string, array{route: string, routeParameters: array<string, string|int>, icon: string, label: string}> $data
     */
    public function addData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * @return array<string, array{route: string, routeParameters: array<string, string|int>, icon: string, label: string}> $data
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setDataKey(string $dataKey): void
    {
        $this->dataKey = $dataKey;
    }

    public function getDataKey(): string
    {
        return $this->dataKey;
    }
}
