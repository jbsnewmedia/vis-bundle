<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Model\Topbar;

class TopbarDropdownProfile extends TopbarDropdown
{
    protected string $avatar = 'jbsnewmedia/vis-bundle/assets/img/profile.jpg';

    public function __construct(
        string $tool,
        string $id = 'dropdown_profile_end',
        string $position = 'end',
    ) {
        parent::__construct($tool, $id);
        $this->setPosition($position);
        $this->setClass('btn btn-link justify-content-center align-items-center avalynx-admin-header-button');
        $this->setContent('<i class="fa-solid fa-user fa-fw"></i>');
        $this->setLabel('Profile');
        $this->setOrder(100);
        $this->setContentFilter('raw');
        $this->setTemplate('@Vis/topbar/dropdown_profile.html.twig');
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }
}
