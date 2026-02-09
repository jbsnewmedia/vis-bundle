<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Entity;

use JBSNewMedia\VisBundle\Entity\Client;
use JBSNewMedia\VisBundle\Entity\ClientToTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Doctrine\Common\Collections\Collection;

class ClientTest extends TestCase
{
    public function testConstructor(): void
    {
        $client = new Client();
        $this->assertInstanceOf(Uuid::class, $client->getId());
        $this->assertInstanceOf(Collection::class, $client->getTools());
        $this->assertCount(0, $client->getTools());
    }

    public function testNumber(): void
    {
        $client = new Client();
        $number = 'C12345';
        $client->setNumber($number);
        $this->assertEquals($number, $client->getNumber());
    }

    public function testTitle(): void
    {
        $client = new Client();
        $title = 'Test Client';
        $client->setTitle($title);
        $this->assertEquals($title, $client->getTitle());
    }

    public function testTimestampsAndCreators(): void
    {
        $client = new Client();
        $now = new \DateTimeImmutable();
        $uuid = Uuid::v7();

        $client->setCreatedAt($now);
        $this->assertSame($now, $client->getCreatedAt());

        $client->setUpdatedAt($now);
        $this->assertSame($now, $client->getUpdatedAt());

        $client->setCreatedBy($uuid);
        $this->assertSame($uuid, $client->getCreatedBy());

        $client->setUpdatedBy($uuid);
        $this->assertSame($uuid, $client->getUpdatedBy());
    }

    public function testLifecycleCallbacks(): void
    {
        $client = new Client();

        $this->assertNull($client->getCreatedAt());
        $this->assertNull($client->getUpdatedAt());

        $client->onPrePersist();

        $this->assertInstanceOf(\DateTimeImmutable::class, $client->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $client->getUpdatedAt());
        $this->assertSame($client->getCreatedAt(), $client->getUpdatedAt());

        $oldUpdatedAt = $client->getUpdatedAt();
        usleep(1000); // Ensure a slight delay

        $client->onPreUpdate();
        $this->assertNotSame($oldUpdatedAt, $client->getUpdatedAt());
    }
}
