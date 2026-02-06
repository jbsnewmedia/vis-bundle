<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JBSNewMedia\VisBundle\Command\VisUserRemoveRoleCommand;
use JBSNewMedia\VisBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class VisUserRemoveRoleCommandTest extends TestCase
{
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->with(User::class)->willReturn($this->repository);
    }

    public function testAbortByEmail(): void
    {
        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $tester->setInputs(['quit']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Command aborted by user', $tester->getDisplay());
    }

    public function testInvalidEmailLoop(): void
    {
        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $tester->setInputs(['invalid-email', 'quit']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('not a valid email', strtolower($tester->getDisplay()));
    }

    public function testUserNotFound(): void
    {
        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $this->repository->method('findOneBy')->willReturn(null);

        $tester->setInputs(['test@example.com']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('User with email test@example.com not found', $tester->getDisplay());
    }

    public function testAbortByRole(): void
    {
        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $user = new User();
        $user->setEmail('test@example.com');
        $this->repository->method('findOneBy')->willReturn($user);

        $tester->setInputs(['test@example.com', 'quit']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Command aborted by user', $tester->getDisplay());
    }

    public function testRoleNotFound(): void
    {
        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_EXISTING']);
        $this->repository->method('findOneBy')->willReturn($user);

        $tester->setInputs(['test@example.com', 'ROLE_NON_EXISTENT']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Role ROLE_NON_EXISTENT not found', $tester->getDisplay());
    }

    public function testRemoveRoleSuccess(): void
    {
        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->addRole('ROLE_ADMIN');
        $this->repository->method('findOneBy')->willReturn($user);

        $this->entityManager->expects($this->once())->method('persist')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $tester->setInputs(['test@example.com', 'ROLE_ADMIN']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
        $this->assertStringContainsString('Role ROLE_ADMIN removed from user with email test@example.com successfully', $display);
        $this->assertNotContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testRemoveRoleException(): void
    {
        $command = new VisUserRemoveRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->addRole('ROLE_ADMIN');
        $this->repository->method('findOneBy')->willReturn($user);

        $this->entityManager->method('flush')->willThrowException(new \Exception());

        $tester->setInputs(['test@example.com', 'ROLE_ADMIN']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('An error occurred while removing role ROLE_ADMIN', $tester->getDisplay());
    }
}
