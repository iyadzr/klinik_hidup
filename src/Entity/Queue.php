<?php

namespace App\Entity;

use App\Repository\QueueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QueueRepository::class)]
class Queue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctor $doctor = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $queueDateTime = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null; // waiting, in_consultation, completed, cancelled

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $queueNumber = null;

    #[ORM\Column(type: "integer")]
    private ?int $registrationNumber = null;

    public function getRegistrationNumber(): ?int
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?int $registrationNumber): self
    {
        $this->registrationNumber = $registrationNumber;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): self
    {
        $this->patient = $patient;
        return $this;
    }

    public function getDoctor(): ?Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(?Doctor $doctor): self
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getQueueDateTime(): ?\DateTimeImmutable
    {
        return $this->queueDateTime;
    }

    public function setQueueDateTime(\DateTimeImmutable $queueDateTime): self
    {
        $this->queueDateTime = $queueDateTime;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getQueueNumber(): ?string
    {
        return $this->queueNumber;
    }

    public function setQueueNumber(string $queueNumber): self
    {
        $this->queueNumber = $queueNumber;
        return $this;
    }
}
