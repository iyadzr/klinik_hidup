<?php

namespace App\Entity;

use App\Repository\PrescribedMedicationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrescribedMedicationRepository::class)]
class PrescribedMedication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Consultation $consultation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Medication $medication = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $quantity = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $instructions = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $actualPrice = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $prescribedAt = null;

    public function __construct()
    {
        $this->prescribedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        $this->consultation = $consultation;
        return $this;
    }

    public function getMedication(): ?Medication
    {
        return $this->medication;
    }

    public function setMedication(?Medication $medication): static
    {
        $this->medication = $medication;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): static
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getActualPrice(): ?string
    {
        return $this->actualPrice;
    }

    public function setActualPrice(?string $actualPrice): static
    {
        $this->actualPrice = $actualPrice;
        return $this;
    }

    /**
     * Helper method to get medication name through the relationship
     */
    public function getName(): ?string
    {
        return $this->medication ? $this->medication->getName() : null;
    }

    public function getPrescribedAt(): ?\DateTimeImmutable
    {
        return $this->prescribedAt;
    }

    public function setPrescribedAt(\DateTimeImmutable $prescribedAt): static
    {
        $this->prescribedAt = $prescribedAt;
        return $this;
    }
} 