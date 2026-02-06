<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Model;

use JBSNewMedia\VisBundle\Model\Tool;
use PHPUnit\Framework\TestCase;

class ToolTest extends TestCase
{
    public function testConstructor(): void
    {
        $tool = new Tool('test_tool', 50);
        $this->assertEquals('test_tool', $tool->getId());
        $this->assertEquals(50, $tool->getPriority());
    }

    public function testDefaultPriority(): void
    {
        $tool = new Tool('test_tool');
        $this->assertEquals(100, $tool->getPriority());
    }

    public function testSettersAndGetters(): void
    {
        $tool = new Tool('test_tool');

        $tool->setId('new_id');
        $this->assertEquals('new_id', $tool->getId());

        $tool->setPriority(200);
        $this->assertEquals(200, $tool->getPriority());

        $tool->setTitle('Tool Title');
        $this->assertEquals('Tool Title', $tool->getTitle());

        $tool->setMerge(true);
        $this->assertTrue($tool->isMerge());
    }

    public function testRoles(): void
    {
        $tool = new Tool('test_tool');
        $this->assertEmpty($tool->getRoles());

        $tool->addRole('ROLE_ADMIN');
        $this->assertTrue($tool->hasRole('ROLE_ADMIN'));
        $this->assertArrayHasKey('ROLE_ADMIN', $tool->getRoles());

        $tool->setRoles(['ROLE_USER', 'ROLE_MANAGER']);
        $this->assertTrue($tool->hasRole('ROLE_USER'));
        $this->assertTrue($tool->hasRole('ROLE_MANAGER'));
        $this->assertFalse($tool->hasRole('ROLE_ADMIN'));

        $tool->removeRole('ROLE_USER');
        $this->assertFalse($tool->hasRole('ROLE_USER'));
    }
}
