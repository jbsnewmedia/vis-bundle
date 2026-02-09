<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Entity;

use JBSNewMedia\VisBundle\Entity\Client;
use JBSNewMedia\VisBundle\Entity\ClientToTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ClientToToolTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $clientToTool = new ClientToTool();

        $this->assertInstanceOf(Uuid::class, $clientToTool->getId());

        $client = new Client();
        $clientToTool->setClient($client);
        $this->assertSame($client, $clientToTool->getClient());

        $clientToTool->setTool('my-tool');
        $this->assertEquals('my-tool', $clientToTool->getTool());

        $userUuid = Uuid::v7();
        $clientToTool->setCreatedBy($userUuid);
        $this->assertSame($userUuid, $clientToTool->getCreatedBy());

        $clientToTool->setUpdatedBy($userUuid);
        $this->assertSame($userUuid, $clientToTool->getUpdatedBy());
    }

    public function testLifecycleCallbacks(): void
    {
        $clientToTool = new ClientToTool();

        $this->assertNull($clientToTool->getCreatedAt());
        $this->assertNull($clientToTool->getUpdatedAt());

        $clientToTool->onPrePersist();

        $this->assertInstanceOf(\DateTimeImmutable::class, $clientToTool->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $clientToTool->getUpdatedAt());
        $this->assertSame($clientToTool->getCreatedAt(), $clientToTool->getUpdatedAt());

        $oldUpdatedAt = $clientToTool->getUpdatedAt();
        usleep(1000);

        $clientToTool->onPreUpdate();
        $this->assertNotSame($oldUpdatedAt, $clientToTool->getUpdatedAt());
    }
}
