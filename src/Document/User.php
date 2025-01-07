<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document]
class User
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(type:"string")]
    private $username;

    #[MongoDB\Field(type:"string")]
    private $email;

    #[MongoDB\Field(type:"string")]
    private $password;

    #[MongoDB\Field(type:"string")]
    private $role;

    #[MongoDB\ReferenceMany(targetDocument: Zone::class, storeAs: "id")]
    private $assignedZones = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getAssignedZones()
    {
        return $this->assignedZones;
    }

    public function setAssignedZones(array $assignedZones): self
    {
        $this->assignedZones = $assignedZones;
        return $this;
    }

    public function addAssignedZone(Zone $zone): self
    {
        if (!in_array($zone, $this->assignedZones, true)) {
            $this->assignedZones[] = $zone;
        }

        return $this;
    }

    public function removeAssignedZone(Zone $zone): self
    {
        $this->assignedZones = array_filter($this->assignedZones, fn ($z) => $z !== $zone);

        return $this;
    }
}
