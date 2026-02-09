<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JBSNewMedia\VisBundle\Command\VisUserAddRoleCommand;
use JBSNewMedia\VisBundle\Command\VisUserRemoveRoleCommand;
use JBSNewMedia\VisBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class VisUserAddRemoveRoleEdgeTest extends TestCase
{
    private EntityManagerInterface $em;
    private EntityRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($this->repo);
    }

    public function testAddRoleInvalidEmailThenQuit(): void
    {
        $command = new VisUserAddRoleCommand($this->em);
        $tester = new CommandTester($command);

        $tester->setInputs(['invalid-email', 'quit']);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('not a valid email', strtolower($display));
        $this->assertStringContainsString('Command aborted by user', $display);
    }

    public function testAddRoleAbortOnRoleInput(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->repo->method('findOneBy')->with(['email' => 'test@example.com'])->willReturn($user);

        $command = new VisUserAddRoleCommand($this->em);
        $tester = new CommandTester($command);

        $tester->setInputs(['test@example.com', 'quit']);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Command aborted by user', $tester->getDisplay());
    }

    public function testAddRoleDbException(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->repo->method('findOneBy')->with(['email' => 'test@example.com'])->willReturn($user);

        $this->em->method('persist');
        $this->em->method('flush')->willThrowException(new \Exception('db down'));

        $command = new VisUserAddRoleCommand($this->em);
        $tester = new CommandTester($command);

        $tester->setInputs(['test@example.com', 'ROLE_ADMIN']);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('An error occurred while adding role', $tester->getDisplay());
    }

    public function testRemoveRoleAbortOnEmail(): void
    {
        $command = new VisUserRemoveRoleCommand($this->em);
        $tester = new CommandTester($command);

        $tester->setInputs(['quit']);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Command aborted by user', $tester->getDisplay());
    }

    public function testRemoveRoleAbortOnRoleInput(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->addRole('ROLE_USER');
        $this->repo->method('findOneBy')->with(['email' => 'test@example.com'])->willReturn($user);

        $command = new VisUserRemoveRoleCommand($this->em);
        $tester = new CommandTester($command);

        $tester->setInputs(['test@example.com', 'quit']);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Command aborted by user', $tester->getDisplay());
    }

    public function testRemoveRoleDbException(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->addRole('ROLE_ADMIN');
        $this->repo->method('findOneBy')->with(['email' => 'test@example.com'])->willReturn($user);

        $this->em->method('persist');
        $this->em->method('flush')->willThrowException(new \Exception('db down'));

        $command = new VisUserRemoveRoleCommand($this->em);
        $tester = new CommandTester($command);

        $tester->setInputs(['test@example.com', 'ROLE_ADMIN']);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('An error occurred while removing role', $tester->getDisplay());
    }
}
