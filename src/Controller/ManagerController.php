<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Controller;

use JBSNewMedia\VisBundle\Service\Vis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ManagerController extends AbstractController
{
    public function __construct(public Vis $vis)
    {
    }

    public function dashboard(): Response
    {
        $this->vis->setTool('manager');
        $this->vis->setRoute('manager', 'dashboard');

        return $this->render('@VisBundle/tool/dashboard.html.twig', [
            'vis' => $this->vis,
        ]);
    }

    public function user(): Response
    {
        $this->vis->setTool('manager');
        $this->vis->setRoute('manager', 'vis-vis2-user2');

        return $this->render('@VisBundle/tool/dashboard.html.twig', [
            'vis' => $this->vis,
        ]);
    }
}
