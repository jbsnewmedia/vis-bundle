<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Topbar;

use JBSNewMedia\VisBundle\Service\Vis;

class TopbarLiveSearchClients extends TopbarLiveSearch
{
    protected Vis $vis;

    protected bool $dataSet = false;

    public function __construct(
        string $tool,
        string $id = 'dropdown_clients_end',
        string $position = 'end',
    ) {
        parent::__construct($tool, $id);
        $this->setPosition($position);
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-simpleadmin-header-button');
        $this->setContent('<i class="fa-solid fa-building fa-fw"></i>');
        $this->setLabel('Clients');
        $this->setLabelSearch('Search');
        $this->setOrder(90);
        $this->setContentFilter('raw');
        $this->setTemplate('@Vis/topbar/livesearch_clients.html.twig');
    }

    public function getLabel(): string
    {
        $title = $this->getVis()->getSelectedClientTitle();
        if ($title) {
            return $title;
        }

        return $this->getVis()->getTranslator()->trans('main.livesearch.client_select', domain: 'vis');
    }

    public function getContent(): string
    {
        return '<i class="fa-solid fa-building fa-fw"></i>';
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
        $selectedId = $this->getVis()->getSelectedClientId();
        foreach ($this->getVis()->getClients() as $id => $title) {
            $data[$id] = [
                'route' => 'vis_api_client',
                'routeparameters' => ['id' => $id],
                'label' => $title,
            ];
        }
        $this->setData($data);
        $this->setDataKey($selectedId);
    }

    public function getDataCounter(): int
    {
        if (true !== $this->isDataSet()) {
            $this->setVisData();
        }

        return parent::getDataCounter();
    }
}
