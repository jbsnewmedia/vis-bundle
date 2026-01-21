<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Controller;

use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Service\Attribute\Required;

class VisAbstractController extends AbstractController
{
    protected Vis $vis;

    #[Required]
    public function setVis(Vis $vis): void
    {
        $this->vis = $vis;
    }
}
