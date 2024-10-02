<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity\Topbar;

class TopbarDropdownProfile extends TopbarDropdown
{
    public function __construct(
        string $tool,
        string $id = 'dropdown_profile_end',
        string $position = 'end',
    ) {
        parent::__construct($tool, $id);
        $this->setPosition($position);
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-simpleadmin-header-button');
        $this->setContent('<i class="fa-solid fa-user fa-fw"></i>');
        $this->setLabel('Profile');
        $this->setOrder(100);
        $this->setContentFilter('raw');
    }
}
