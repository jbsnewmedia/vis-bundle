<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class VisPlugin
{
    public function __construct(
        public readonly ?string $plugin = null,
        public readonly int $priority = 0,
    ) {
    }
}
