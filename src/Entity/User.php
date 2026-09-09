<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JBSNewMedia\VisBundle\Repository\VisUserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VisUserRepository::class)]
#[ORM\Table(name: '`vis_user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $createdBy = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $updatedBy = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    /**
     * @var Collection<int, UserToClient>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserToClient::class)]
    private Collection $clients;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->clients = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        if (empty($this->email)) {
            throw new \LogicException('User email is not set.');
        }

        return $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        /** @var list<string> $rolesList */
        $rolesList = array_values(array_unique($roles));

        return $rolesList;
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function addRole(string $role): static
    {
        $this->roles[] = $role;

        /** @var list<string> $rolesList */
        $rolesList = array_values(array_unique($this->roles));
        $this->roles = $rolesList;

        return $this;
    }

    public function removeRole(string $role): static
    {
        $filtered = [];
        foreach ($this->roles as $r) {
            if ($r !== $role) {
                $filtered[] = $r;
            }
        }
        $this->roles = $filtered;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return Collection<int, UserToClient>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addUserToClient(UserToClient $userToClient): static
    {
        if (!$this->clients->contains($userToClient)) {
            $this->clients->add($userToClient);
            $userToClient->setUser($this);
        }

        return $this;
    }

    public function removeUserToClient(UserToClient $userToClient): static
    {
        if ($this->clients->removeElement($userToClient)) {
            if ($userToClient->getUser() === $this) {
                $userToClient->setUser($this);
            }
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getManagedClientIds(): array
    {
        $ids = [];
        foreach ($this->clients as $userToClient) {
            $client = $userToClient->getClient();
            if (null !== $client && null !== $client->getId()) {
                $ids[] = (string) $client->getId();
            }
        }

        return array_values(array_unique($ids));
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): ?Uuid
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Uuid $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?Uuid
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?Uuid $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
    }
}
