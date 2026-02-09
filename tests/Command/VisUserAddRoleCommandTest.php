<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JBSNewMedia\VisBundle\Command\VisUserAddRoleCommand;
use JBSNewMedia\VisBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class VisUserAddRoleCommandTest extends TestCase
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
        $command = new VisUserAddRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $tester->setInputs(['quit']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Command aborted by user', $tester->getDisplay());
    }

    public function testInvalidEmailLoop(): void
    {
        $command = new VisUserAddRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $tester->setInputs(['invalid-email', 'quit']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('not a valid email', strtolower($tester->getDisplay()));
    }

    public function testUserNotFound(): void
    {
        $command = new VisUserAddRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $this->repository->method('findOneBy')->willReturn(null);

        $tester->setInputs(['test@example.com']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('User with email test@example.com not found', $tester->getDisplay());
    }

    public function testAbortByRole(): void
    {
        $command = new VisUserAddRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $user = new User();
        $user->setEmail('test@example.com');
        $this->repository->method('findOneBy')->willReturn($user);

        $tester->setInputs(['test@example.com', 'quit']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Command aborted by user', $tester->getDisplay());
    }

    public function testAddRoleSuccess(): void
    {
        $command = new VisUserAddRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $user = new User();
        $user->setEmail('test@example.com');
        $this->repository->method('findOneBy')->willReturn($user);

        $this->entityManager->expects($this->once())->method('persist')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $tester->setInputs(['test@example.com', 'ROLE_ADMIN']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
        $this->assertStringContainsString('Role ROLE_ADMIN added to user with email test@example.com successfully', $display);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testAddRoleException(): void
    {
        $command = new VisUserAddRoleCommand($this->entityManager);
        $tester = new CommandTester($command);

        $user = new User();
        $user->setEmail('test@example.com');
        $this->repository->method('findOneBy')->willReturn($user);

        $this->entityManager->method('flush')->willThrowException(new \Exception());

        $tester->setInputs(['test@example.com', 'ROLE_ADMIN']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('An error occurred while adding role ROLE_ADMIN', $tester->getDisplay());
    }
}
