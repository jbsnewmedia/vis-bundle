<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Topbar;

use JBSNewMedia\VisBundle\Service\Vis;

class TopbarLiveSearchTools extends TopbarLiveSearch
{
    protected Vis $vis;

    protected bool $dataSet = false;

    public function __construct(
        string $tool,
        string $id = 'toggle_tools_end',
        string $position = 'end',
    ) {
        parent::__construct($tool, $id);
        $this->setPosition($position);
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-simpleadmin-header-button');
        $this->setContent('<i class="fa-solid fa-diagram-project fa-fw"></i>');
        $this->setLabel('Toggle Sidebar');
        $this->setLabelSearch('Search');
        $this->setOrder(100);
        $this->setContentFilter('raw');
    }

    public function setVis(Vis $vis): void
    {
        $this->vis = $vis;
    }

    public function getVis(): Vis
    {
        return $this->vis;
    }

    protected function isDataSet(): bool
    {
        return $this->dataSet;
    }

    protected function setVisData(): void
    {
        $this->dataSet = true;
        $data = [];
        foreach ($this->getVis()->getTools() as $tool) {
            $data[$tool->getId()] = [
                'route' => 'vis_'.$tool->getId(),
                'routeParameters' => [],
                'label' => $tool->getTitle(),
            ];
        }
        $this->setData($data);
        $this->setDataKey($this->getVis()->getToolId());
    }

    public function getDataCounter(): int
    {
        if (true !== $this->isDataSet()) {
            $this->setVisData();
        }

        return parent::getDataCounter();
    }
}
