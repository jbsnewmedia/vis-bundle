<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class VisPlugin
{
    public function __construct(
        public readonly ?string $plugin = null,
    ) {
    }
}
