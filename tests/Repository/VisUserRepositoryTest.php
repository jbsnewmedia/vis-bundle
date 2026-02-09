<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JBSNewMedia\VisBundle\Entity\User;
use JBSNewMedia\VisBundle\Repository\VisUserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class VisUserRepositoryTest extends TestCase
{
    private $registry;
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Mocking the parent constructor behavior of ServiceEntityRepository
        $this->registry->method('getManagerForClass')->willReturn($this->entityManager);
        $classMetadata = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $classMetadata->name = User::class;
        $this->entityManager->method('getClassMetadata')->willReturn($classMetadata);

        $this->repository = new VisUserRepository($this->registry);
    }

    public function testUpgradePassword(): void
    {
        $user = new User();
        $newPassword = 'new_hashed_password';

        $this->entityManager->expects($this->once())->method('persist')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $this->repository->upgradePassword($user, $newPassword);
        $this->assertEquals($newPassword, $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        $unsupportedUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->repository->upgradePassword($unsupportedUser, 'password');
    }
}
