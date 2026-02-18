<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DarkmodeController extends VisAbstractController
{
    #[Route('/vis/api/darkmode/{status}', name: 'vis_api_darkmode')]
    public function setDarkmode(Request $request, string $status): JsonResponse
    {
        $session = $request->getSession();
        $oldValue = $session->get('darkmode');
        $session->set('darkmode', $status);
        $session->save();

        return new JsonResponse([
            'darkmode' => $status,
            'old_value' => $oldValue,
            'new_value' => $status,
            'success' => true,
        ]);
    }
}
