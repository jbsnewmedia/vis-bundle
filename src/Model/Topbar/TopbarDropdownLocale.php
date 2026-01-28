<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Topbar;

class TopbarDropdownLocale extends TopbarDropdown
{
    public function __construct(
        string $tool,
        string $id = 'dropdown_locale',
        string $position = 'end',
    ) {
        parent::__construct($tool, $id);
        $this->setPosition($position);
        $this->setType('dropdown_locale');
        $this->setClass('btn btn-link justify-content-center align-items-center dropdown-toggle avalynx-simpleadmin-header-button');
        $this->setOrder(90);
        $this->generateTemplate();
    }
}
