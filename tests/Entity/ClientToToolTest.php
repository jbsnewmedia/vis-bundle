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

        $now = new \DateTimeImmutable();
        $clientToTool->setCreatedAt($now);
        $this->assertSame($now, $clientToTool->getCreatedAt());

        $clientToTool->setUpdatedAt($now);
        $this->assertSame($now, $clientToTool->getUpdatedAt());

        $userUuid = Uuid::v7();
        $clientToTool->setCreatedBy($userUuid);
        $this->assertSame($userUuid, $clientToTool->getCreatedBy());

        $clientToTool->setUpdatedBy($userUuid);
        $this->assertSame($userUuid, $clientToTool->getUpdatedBy());
    }
}
