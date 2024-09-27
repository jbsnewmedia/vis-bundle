<?php

namespace JBSNewMedia\VisBundle\Trait;

trait RolesTrait
{

    /**
     * @var array<string, string>
     */
    protected array $roles = [];

    public function addRole(string $role): void
    {
        $this->roles[$role] = $role;
    }

    public function removeRole(string $role): void
    {
        unset($this->roles[$role]);
    }

    /**
     * @return array<string, string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array<string, string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function hasRole(string $role): bool
    {
        return isset($this->roles[$role]);
    }

}