<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use JBSNewMedia\VisBundle\Plugin\PluginInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class VisPluginCollector
{
    /**
     * @param iterable<int, PluginInterface> $taggedServices ordered by priority DESC by Symfony, we'll reverse to ASC
     */
    public function __construct(
        #[TaggedIterator('VisPlugin')]
        private readonly iterable $taggedServices,
    ) {
    }

    public function processAll(): void
    {
        $services = array_reverse(iterator_to_array($this->taggedServices));

        foreach (['init', 'setTopbar', 'setNavigation'] as $method) {
            foreach ($services as $service) {
                $service->$method();
            }
        }
    }

    /**
     * @return array<int, PluginInterface>
     */
    public function getServices(): array
    {
        return array_reverse(iterator_to_array($this->taggedServices));
    }

    public function getServiceCount(): int
    {
        return count($this->getServices());
    }

    public function getByPlugin(string $plugin): ?PluginInterface
    {
        foreach ($this->getServices() as $service) {
            if ($service->getPluginId() === $plugin) {
                return $service;
            }
        }

        return null;
    }
}
