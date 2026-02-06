<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JBSNewMedia\VisBundle\Command\VisUserAddRoleCommand;
use JBSNewMedia\VisBundle\Command\VisUserRemoveRoleCommand;
use JBSNewMedia\VisBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class VisUserRoleCommandsTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->with(User::class)->willReturn($this->repository);
    }

    public function testAddRoleCommandAbort(): void
    {
        $command = new VisUserAddRoleCommand($this->entityManager);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['quit']);
        $commandTester->execute([]);

        $this->assertStringContainsString('Command aborted by user', $commandTester->getDisplay());
    }

    public function testAddRoleCommandSuccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->repository->method('findOneBy')->with(['email' => 'test@example.com'])->willReturn($user);

        $command = new VisUserAddRoleCommand($this->entityManager);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['test@example.com', 'ROLE_ADMIN']);
        $commandTester->execute([]);

        $this->assertStringContainsString('Role ROLE_ADMIN added to user', $commandTester->getDisplay());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testRemoveRoleCommandSuccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->addRole('ROLE_ADMIN');

        $this->repository->method('findOneBy')->with(['email' => 'test@example.com'])->willReturn($user);

        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['test@example.com', 'ROLE_ADMIN']);
        $commandTester->execute([]);

        $this->assertStringContainsString('Role ROLE_ADMIN removed from user', $commandTester->getDisplay());
        $this->assertNotContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testRemoveRoleCommandNotFound(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->repository->method('findOneBy')->with(['email' => 'test@example.com'])->willReturn($user);

        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['test@example.com', 'ROLE_NON_EXISTENT']);
        $commandTester->execute([]);

        $this->assertStringContainsString('Role ROLE_NON_EXISTENT not found', $commandTester->getDisplay());
    }
}
