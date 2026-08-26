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

    protected function setUp(): void
    {
        $this->registry = $this->createStub(ManagerRegistry::class);
    }

    private function createRepository(EntityManagerInterface $entityManager): VisUserRepository
    {
        $this->registry->method('getManagerForClass')->willReturn($entityManager);
        $classMetadata = $this->createStub(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $classMetadata->name = User::class;
        $entityManager->method('getClassMetadata')->willReturn($classMetadata);

        return new VisUserRepository($this->registry);
    }

    public function testUpgradePassword(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createRepository($entityManager);

        $user = new User();
        $newPassword = 'new_hashed_password';

        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $repository->upgradePassword($user, $newPassword);
        $this->assertEquals($newPassword, $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $repository = $this->createRepository($entityManager);

        $unsupportedUser = $this->createStub(PasswordAuthenticatedUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $repository->upgradePassword($unsupportedUser, 'password');
    }
}
