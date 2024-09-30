<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Topbar;

class TopbarLiveSearch extends Topbar
{
    public function __construct(
        string $tool,
        string $id,
    ) {
        parent::__construct($tool, $id);
        $this->setType('livesearch');
        $this->generateTemplate();
    }
}
