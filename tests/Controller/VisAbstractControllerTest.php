<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Controller;

use JBSNewMedia\VisBundle\Controller\VisAbstractController;
use JBSNewMedia\VisBundle\Service\Vis;
use PHPUnit\Framework\TestCase;

class VisAbstractControllerTest extends TestCase
{
    public function testSetVis(): void
    {
        $vis = $this->createMock(Vis::class);
        $controller = new class extends VisAbstractController {
            public function getVis(): Vis
            {
                return $this->vis;
            }
        };

        $controller->setVis($vis);
        $this->assertSame($vis, $controller->getVis());
    }
}
