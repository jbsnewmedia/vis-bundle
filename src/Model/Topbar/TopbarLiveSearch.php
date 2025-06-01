<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Topbar;

class TopbarLiveSearch extends Topbar
{
    /**
     * @var array<string, array{route: string, routeParameters: array<string, string|int>, label: string}>
     */
    protected array $data = [];

    protected int $dataCounter = 0;

    protected string $dataKey = '';

    protected string $labelSearch = '';

    protected int $countForSearch = 0;

    public function __construct(
        string $tool,
        string $id,
    ) {
        parent::__construct($tool, $id);
        $this->setType('livesearch');
        $this->setLabelSearch('Search');
        $this->setCountForSearch(10);
        $this->generateTemplate();
    }

    /**
     * @param array<string, array{route: string, routeParameters: array<string, string|int>, label: string}> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
        $this->setDataCounter(count($data));
    }

    /**
     * @param array<string, array{route: string, routeParameters: array<string, string|int>, label: string}> $data
     */
    public function addData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
        $this->setDataCounter(count($this->data));
    }

    /**
     * @return array<string, array{route: string, routeParameters: array<string, string|int>, label: string}> $data
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setDataCounter(int $dataCounter): void
    {
        $this->dataCounter = $dataCounter;
    }

    public function getDataCounter(): int
    {
        return $this->dataCounter;
    }

    public function setDataKey(string $dataKey): void
    {
        $this->dataKey = $dataKey;
    }

    public function getDataKey(): string
    {
        return $this->dataKey;
    }

    public function setLabelSearch(string $labelSearch): void
    {
        $this->labelSearch = $labelSearch;
    }

    public function getLabelSearch(): string
    {
        return $this->labelSearch;
    }

    public function setCountForSearch(int $countForSearch): void
    {
        $this->countForSearch = $countForSearch;
    }

    public function getCountForSearch(): int
    {
        return $this->countForSearch;
    }
}
