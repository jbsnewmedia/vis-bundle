<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Plugin;

use JBSNewMedia\VisBundle\Plugin\AbstractBundle;
use PHPUnit\Framework\TestCase;

class AbstractBundleTest extends TestCase
{
    public function testActivate(): void
    {
        $bundle = new class extends AbstractBundle {};
        $bundle->activate();
        $this->assertTrue(true); // Verifies that the method exists and can be called
    }

    public function testUpdate(): void
    {
        $bundle = new class extends AbstractBundle {};
        $bundle->update();
        $this->assertTrue(true); // Verifies that the method exists and can be called
    }
}
