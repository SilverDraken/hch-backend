<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use DateTime;

#[MongoDB\Document]
class Intervention
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(type: "string")]
    private $type;

    #[MongoDB\Field(type: "date")]
    private $createdDate;

    #[MongoDB\Field(type: "date")]
    private $dateOfIntervention;

    #[MongoDB\Field(type: "string")]
    private $bikeModel;

    #[MongoDB\EmbedOne(targetDocument: Client::class)]
    private $client;

    #[MongoDB\ReferenceOne(targetDocument: Zone::class, storeAs: "id")]
    private $zone;

    #[MongoDB\ReferenceOne(targetDocument: User::class, storeAs: "id")]
    private $technician;

    #[MongoDB\Field(type: "string")]
    private $time;

    #[MongoDB\Field(type: "int")]
    private $length;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getCreatedDate(): ?DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    public function getDateOfIntervention(): ?DateTime
    {
        return $this->dateOfIntervention;
    }

    public function setDateOfIntervention(DateTime $dateOfIntervention): self
    {
        $this->dateOfIntervention = $dateOfIntervention;
        return $this;
    }

    public function getBikeModel(): ?string
    {
        return $this->bikeModel;
    }

    public function setBikeModel(string $bikeModel): self
    {
        $this->bikeModel = $bikeModel;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;
        return $this;
    }

    public function getTechnician(): ?User
    {
        return $this->technician;
    }

    public function setTechnician(?User $technician): self
    {
        $this->technician = $technician;
        return $this;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function setTime(string $time): self
    {
        $this->time = $time;
        return $this;
    }
    

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }
}
