<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\VisBundle\Command\VisUserCreateCommand;
use JBSNewMedia\VisBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class VisUserCreateCommandEdgeTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
    }

    public function testInvalidEmailThenQuit(): void
    {
        $command = new VisUserCreateCommand($this->hasher, $this->entityManager);
        $tester = new CommandTester($command);

        // First: invalid email -> validator error, then quit
        $tester->setInputs(['not-an-email', 'quit']);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('not a valid email', strtolower($display));
        $this->assertStringContainsString('Command aborted by user', $display);
    }

    public function testTooShortPasswordThenSuccess(): void
    {
        $command = new VisUserCreateCommand($this->hasher, $this->entityManager);
        $tester = new CommandTester($command);

        $email = 'user@example.com';

        $this->hasher
            ->method('hashPassword')
            ->willReturn('hashed_pw');

        // Expect persist with User object that has email set
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) use ($email) {
                return $user instanceof User && $user->getEmail() === $email;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Inputs: valid email, short password (invalid), then valid password
        $tester->setInputs([$email, '123', '123456']);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        // Length validator typical message contains "too short" and minimum length 6
        $this->assertStringContainsString('6', $display);
        $this->assertStringContainsString('created successfully', strtolower($display));
    }

    public function testPersistFlushExceptionShowsError(): void
    {
        $command = new VisUserCreateCommand($this->hasher, $this->entityManager);
        $tester = new CommandTester($command);

        $this->hasher->method('hashPassword')->willReturn('hashed_pw');

        $this->entityManager->method('persist');
        $this->entityManager->method('flush')->willThrowException(new \Exception('db down'));

        $tester->setInputs(['user@example.com', '123456']);
        $exitCode = $tester->execute([]);

        // Even on error the command returns SUCCESS (after displaying error) per implementation
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('An error occurred while creating the user', $tester->getDisplay());
    }
}
