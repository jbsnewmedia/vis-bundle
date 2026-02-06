<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Core\Exception;

use JBSNewMedia\VisBundle\Core\Exception\KernelPluginLoaderException;
use PHPUnit\Framework\TestCase;

class KernelPluginLoaderExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new KernelPluginLoaderException('MyPlugin', 'Something went wrong');

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertStringContainsString('MyPlugin', $exception->getMessage());
        $this->assertStringContainsString('Something went wrong', $exception->getMessage());
        $this->assertEquals('FRAMEWORK__KERNEL_PLUGIN_LOADER_ERROR', $exception->getErrorCode());
    }
}
