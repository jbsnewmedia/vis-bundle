<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Extension;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\VisBundle\Entity\Client;
use JBSNewMedia\VisBundle\Entity\User;
use JBSNewMedia\VisBundle\Entity\UserToClient;
use JBSNewMedia\VisBundle\Service\ClientMembershipManager;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Uid\Uuid;

/**
 * Default client membership implementation backed by the UserToClient
 * entity. Plugins may register additional extensions by tagging their
 * service with "vis.client_membership_extension".
 */
#[AutoconfigureTag('vis.client_membership_extension')]
class UserClientMembershipExtension implements ClientMembershipExtensionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientMembershipManager $clientMembershipManager,
    ) {
    }

    public function getExtensionId(): string
    {
        return 'user_client';
    }

    public function getExtensionLabel(): string
    {
        return 'User client assignments';
    }

    public function getMembershipsForUser(object $user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $clients = $this->clientMembershipManager->filterManaged(
            $user,
            $this->clientMembershipManager->getAvailableClients()
        );

        $memberships = [];
        foreach ($clients as $id => $title) {
            $memberships[] = ['id' => $id, 'title' => $title];
        }

        return $memberships;
    }

    public function getMembersForClient(string $clientId): array
    {
        $client = $this->resolveClient($clientId);
        if (null === $client) {
            return [];
        }

        $relations = $this->entityManager->getRepository(UserToClient::class)->findBy(['client' => $client]);

        $members = [];
        foreach ($relations as $userToClient) {
            $user = $userToClient->getUser();
            if ($user instanceof User && null !== $user->getId()) {
                $members[] = [
                    'id' => (string) $user->getId(),
                    'email' => (string) $user->getEmail(),
                    'role' => 'member',
                ];
            }
        }

        return $members;
    }

    public function addMember(string $clientId, string $userId, string $role = 'member'): bool
    {
        $client = $this->resolveClient($clientId);
        $user = $this->resolveUser($userId);

        if (null === $client || null === $user) {
            return false;
        }

        foreach ($user->getClients() as $userToClient) {
            if ($userToClient->getClient() === $client) {
                return true;
            }
        }

        $userToClient = new UserToClient();
        $userToClient->setUser($user);
        $userToClient->setClient($client);
        $this->entityManager->persist($userToClient);

        return true;
    }

    public function removeMember(string $clientId, string $userId): bool
    {
        $client = $this->resolveClient($clientId);
        $user = $this->resolveUser($userId);

        if (null === $client || null === $user) {
            return false;
        }

        $removed = false;
        foreach ($user->getClients() as $userToClient) {
            if ($userToClient->getClient() === $client) {
                $this->entityManager->remove($userToClient);
                $removed = true;
            }
        }

        return $removed;
    }

    private function resolveClient(string $clientId): ?Client
    {
        try {
            $uuid = Uuid::fromString($clientId);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $client = $this->entityManager->find(Client::class, $uuid);

        return $client instanceof Client ? $client : null;
    }

    private function resolveUser(string $userId): ?User
    {
        try {
            $uuid = Uuid::fromString($userId);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $user = $this->entityManager->find(User::class, $uuid);

        return $user instanceof User ? $user : null;
    }
}
