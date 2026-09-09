<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Extension;

/**
 * Plugins can implement this interface to expose a way to manage
 * user-to-client memberships.
 */
interface ClientMembershipExtensionInterface
{
    /**
     * Returns the list of clients the user has access to.
     *
     * @return array<int, array{id: string, title: string}>
     */
    public function getMembershipsForUser(object $user): array;

    /**
     * Returns the list of users in the client.
     *
     * @return array<int, array{id: string, email: string, role: string}>
     */
    public function getMembersForClient(string $clientId): array;

    /**
     * Grants the user access to the client.
     */
    public function addMember(string $clientId, string $userId, string $role = 'member'): bool;

    /**
     * Revokes the user's access to the client.
     */
    public function removeMember(string $clientId, string $userId): bool;

    /**
     * Returns a unique identifier for this extension (used in the UI).
     */
    public function getExtensionId(): string;

    /**
     * Returns a human-readable label for this extension.
     */
    public function getExtensionLabel(): string;
}
