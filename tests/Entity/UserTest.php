<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Entity;

use JBSNewMedia\VisBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UserTest extends TestCase
{
    public function testConstructorGeneratesUuid(): void
    {
        $user = new User();
        $this->assertInstanceOf(Uuid::class, $user->getId());
    }

    public function testEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';
        $user->setEmail($email);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->getUserIdentifier());
    }

    public function testUserIdentifierThrowsExceptionWhenEmailNotSet(): void
    {
        $user = new User();
        $this->expectException(\LogicException::class);
        $user->getUserIdentifier();
    }

    public function testRoles(): void
    {
        $user = new User();
        $this->assertContains('ROLE_USER', $user->getRoles());

        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());

        $user->addRole('ROLE_MANAGER');
        $this->assertContains('ROLE_MANAGER', $user->getRoles());

        $user->removeRole('ROLE_ADMIN');
        $this->assertNotContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_MANAGER', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());

        // Test removing non-existent role
        $user->removeRole('ROLE_NON_EXISTENT');
        $this->assertContains('ROLE_MANAGER', $user->getRoles());
    }

    public function testPassword(): void
    {
        $user = new User();
        $password = 'hashed_password';
        $user->setPassword($password);
        $this->assertEquals($password, $user->getPassword());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->eraseCredentials();
        $this->assertTrue(true); // Just to verify it can be called
    }

    public function testTimestampsAndCreators(): void
    {
        $user = new User();
        $now = new \DateTimeImmutable();
        $uuid = Uuid::v7();

        $user->setCreatedAt($now);
        $this->assertSame($now, $user->getCreatedAt());

        $user->setUpdatedAt($now);
        $this->assertSame($now, $user->getUpdatedAt());

        $user->setCreatedBy($uuid);
        $this->assertSame($uuid, $user->getCreatedBy());

        $user->setUpdatedBy($uuid);
        $this->assertSame($uuid, $user->getUpdatedBy());
    }

    public function testLifecycleCallbacks(): void
    {
        $user = new User();

        $this->assertNull($user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());

        $user->onPrePersist();

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
        $this->assertSame($user->getCreatedAt(), $user->getUpdatedAt());

        $oldUpdatedAt = $user->getUpdatedAt();
        usleep(1000);

        $user->onPreUpdate();
        $this->assertNotSame($oldUpdatedAt, $user->getUpdatedAt());
    }
}
