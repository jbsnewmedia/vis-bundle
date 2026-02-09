<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Tests\Trait;

use JBSNewMedia\VisBundle\Trait\RolesTrait;
use PHPUnit\Framework\TestCase;

class RolesTraitTest extends TestCase
{
    private $traitObject;

    protected function setUp(): void
    {
        $this->traitObject = new class {
            use RolesTrait;
        };
    }

    public function testAddRole(): void
    {
        $this->traitObject->addRole('ROLE_ADMIN');
        $this->assertTrue($this->traitObject->hasRole('ROLE_ADMIN'));
        $this->assertContains('ROLE_ADMIN', $this->traitObject->getRoles());
    }

    public function testRemoveRole(): void
    {
        $this->traitObject->addRole('ROLE_USER');
        $this->traitObject->removeRole('ROLE_USER');
        $this->assertFalse($this->traitObject->hasRole('ROLE_USER'));
        $this->assertNotContains('ROLE_USER', $this->traitObject->getRoles());
    }

    public function testSetAndGetRoles(): void
    {
        $roles = ['ROLE_MANAGER' => 'ROLE_MANAGER', 'ROLE_EDITOR' => 'ROLE_EDITOR'];
        $this->traitObject->setRoles($roles);
        $this->assertEquals($roles, $this->traitObject->getRoles());
    }

    public function testHasRole(): void
    {
        $this->assertFalse($this->traitObject->hasRole('ROLE_UNKNOWN'));
        $this->traitObject->addRole('ROLE_KNOWN');
        $this->assertTrue($this->traitObject->hasRole('ROLE_KNOWN'));
    }

    public function testSetRolesWithNumericKeys(): void
    {
        $roles = ['ROLE_A', 'ROLE_B'];
        $this->traitObject->setRoles($roles);
        $expected = ['ROLE_A' => 'ROLE_A', 'ROLE_B' => 'ROLE_B'];
        $this->assertEquals($expected, $this->traitObject->getRoles());
    }
}
