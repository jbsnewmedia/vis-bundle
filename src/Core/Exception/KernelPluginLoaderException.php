<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Core\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class KernelPluginLoaderException extends HttpException
{
    public function __construct(string $plugin, string $reason)
    {
        parent::__construct(
            500,
            'Failed to load plugin "'.$plugin.'". Reason: '.$reason
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__KERNEL_PLUGIN_LOADER_ERROR';
    }
}
