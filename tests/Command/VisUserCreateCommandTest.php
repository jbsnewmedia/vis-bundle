<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\VisBundle\Command\VisUserCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class VisUserCreateCommandTest extends TestCase
{
    public function testCreateUserCommandAbort(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $command = new VisUserCreateCommand($hasher, $em);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['quit']);
        $commandTester->execute([]);

        $this->assertStringContainsString('Command aborted by user', $commandTester->getDisplay());
    }

    public function testCreateUserCommandSuccess(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $hasher->method('hashPassword')->willReturn('hashed_password');

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $command = new VisUserCreateCommand($hasher, $em);
        $commandTester = new CommandTester($command);

        // Inputs: email, password
        $commandTester->setInputs(['test@example.com', 'password123']);
        $commandTester->execute([]);

        $this->assertStringContainsString('created successfully', $commandTester->getDisplay());
    }

    public function testCreateUserCommandInvalidEmailThenQuit(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $command = new VisUserCreateCommand($hasher, $em);
        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['invalid-email', 'quit']);
        $commandTester->execute([]);

        $this->assertStringContainsString('This value is not a valid email address', $commandTester->getDisplay());
    }
}
