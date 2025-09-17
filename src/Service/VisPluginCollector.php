<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class VisPluginCollector
{
    public function __construct(
        #[TaggedIterator('VisPlugin')]
        private readonly iterable $taggedServices,
    ) {
    }

    public function processAll(): array
    {
        $results = [];

        foreach ($this->taggedServices as $service) {
            if (method_exists($service, 'init')) {
                $results[] = $service->init();
            }
        }

        foreach ($this->taggedServices as $service) {
            if (method_exists($service, 'setNavigation')) {
                $results[] = $service->setNavigation();
            }
        }

        return $results;
    }

    public function getServices(): array
    {
        return iterator_to_array($this->taggedServices);
    }

    public function getServiceCount(): int
    {
        return count($this->getServices());
    }
}
