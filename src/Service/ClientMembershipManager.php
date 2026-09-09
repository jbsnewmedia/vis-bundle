<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\VisBundle\Entity\Client;
use JBSNewMedia\VisBundle\Entity\ClientToTool;
use JBSNewMedia\VisBundle\Entity\User;
use JBSNewMedia\VisBundle\Entity\UserToClient;
use Symfony\Component\Uid\Uuid;

/**
 * Central service for user-to-client memberships: which clients a user may
 * manage. Backed by the UserToClient entity.
 */
class ClientMembershipManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Returns true when the user manages the given client.
     */
    public function isManaged(User $user, string $clientId): bool
    {
        try {
            $uuid = Uuid::fromString($clientId);
        } catch (\InvalidArgumentException) {
            return false;
        }

        $client = $this->entityManager->getReference(Client::class, $uuid);

        return null !== $this->entityManager->getRepository(UserToClient::class)
            ->findOneBy(['user' => $user, 'client' => $client]);
    }

    /**
     * @return list<string>
     */
    public function getManagedClientIds(User $user): array
    {
        return $user->getManagedClientIds();
    }

    /**
     * Filters an id => title map down to the clients managed by the user
     * and sorts it by title.
     *
     * @param array<string, string> $clientData
     *
     * @return array<string, string>
     */
    public function filterManaged(User $user, array $clientData): array
    {
        $filtered = array_intersect_key($clientData, array_flip($this->getManagedClientIds($user)));
        asort($filtered);

        return $filtered;
    }

    /**
     * All clients as id => title, sorted by title.
     *
     * @return array<string, string>
     */
    public function getAvailableClients(): array
    {
        $clients = $this->entityManager->getRepository(Client::class)->findBy([], ['title' => 'ASC']);

        $result = [];
        foreach ($clients as $client) {
            if (null !== $client->getId()) {
                $result[(string) $client->getId()] = (string) $client->getTitle();
            }
        }

        return $result;
    }

    /**
     * All clients linked to a tool via ClientToTool as id => title.
     *
     * @return array<string, string>
     */
    public function getClientsForTool(string $toolId): array
    {
        $relations = $this->entityManager
            ->getRepository(ClientToTool::class)
            ->findBy(['tool' => $toolId]);

        $result = [];
        foreach ($relations as $relation) {
            $client = $relation->getClient();
            if ($client instanceof Client && null !== $client->getId()) {
                $result[(string) $client->getId()] = (string) $client->getTitle();
            }
        }

        return $result;
    }

    /**
     * Replaces the clients managed by the user with the given client ids.
     * Unknown client ids are ignored. The caller is responsible for flushing.
     *
     * @param list<string> $clientIds
     */
    public function setManagedClients(User $user, array $clientIds): void
    {
        $selectedClients = array_map(strval(...), $clientIds);
        $existingClients = [];
        foreach ($user->getClients() as $userToClient) {
            $client = $userToClient->getClient();
            if (null !== $client && null !== $client->getId()) {
                $existingClients[(string) $client->getId()] = $userToClient;
            }
        }

        foreach ($selectedClients as $clientId) {
            if (isset($existingClients[$clientId])) {
                continue;
            }

            $client = $this->entityManager->find(Client::class, $clientId);
            if (!$client instanceof Client) {
                continue;
            }

            $userToClient = new UserToClient();
            $userToClient->setUser($user);
            $userToClient->setClient($client);
            $this->entityManager->persist($userToClient);
        }

        foreach ($existingClients as $clientId => $userToClient) {
            if (!in_array($clientId, $selectedClients, true)) {
                $this->entityManager->remove($userToClient);
            }
        }
    }
}
