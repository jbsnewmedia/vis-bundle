<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use JBSNewMedia\VisBundle\Plugin\PluginInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class VisPluginCollector
{
    /**
     * @param iterable<PluginInterface> $taggedServices
     */
    public function __construct(
        #[TaggedIterator('VisPlugin')]
        private readonly iterable $taggedServices,
    ) {
    }

    public function processAll(): void
    {
        foreach ($this->taggedServices as $service) {
            $service->init();
        }

        foreach ($this->taggedServices as $service) {
            $service->setNavigation();
        }
    }

    /**
     * @return array<int, PluginInterface>
     */
    public function getServices(): array
    {
        return iterator_to_array($this->taggedServices);
    }

    public function getServiceCount(): int
    {
        return count($this->getServices());
    }
}
