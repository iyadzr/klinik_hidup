<?php

namespace App\Entity;

use App\Repository\ReceiptCounterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReceiptCounterRepository::class)]
#[ORM\Table(name: 'receipt_counter')]
class ReceiptCounter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $counterName = 'main';

    #[ORM\Column(type: 'integer')]
    private int $currentNumber = 773300;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $lastUpdated;

    public function __construct()
    {
        $this->lastUpdated = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCounterName(): string
    {
        return $this->counterName;
    }

    public function setCounterName(string $counterName): self
    {
        $this->counterName = $counterName;
        return $this;
    }

    public function getCurrentNumber(): int
    {
        return $this->currentNumber;
    }

    public function setCurrentNumber(int $currentNumber): self
    {
        $this->currentNumber = $currentNumber;
        $this->lastUpdated = new \DateTime();
        return $this;
    }

    public function incrementAndGet(): int
    {
        $this->currentNumber++;
        $this->lastUpdated = new \DateTime();
        return $this->currentNumber;
    }

    public function getLastUpdated(): \DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }
} 