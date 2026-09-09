<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Extension;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ClientMembershipRegistry
{
    /** @param iterable<ClientMembershipExtensionInterface> $extensions */
    public function __construct(
        #[AutowireIterator('vis.client_membership_extension')]
        private readonly iterable $extensions,
    ) {
    }

    /** @return list<ClientMembershipExtensionInterface> */
    public function getExtensions(): array
    {
        if ($this->extensions instanceof \Traversable) {
            return array_values(iterator_to_array($this->extensions, false));
        }

        return array_values($this->extensions);
    }

    public function getExtension(string $id): ?ClientMembershipExtensionInterface
    {
        foreach ($this->getExtensions() as $ext) {
            if ($ext->getExtensionId() === $id) {
                return $ext;
            }
        }

        return null;
    }
}
