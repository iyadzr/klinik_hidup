<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctor $doctor = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $consultationDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $diagnosis = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $medications = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $symptoms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $treatment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $followUpPlan = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $consultationFee = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $medicinesFee = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: 'boolean')]
    private bool $isPaid = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $receiptNumber = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $hasMedicalCertificate = false;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $mcStartDate = null;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $mcEndDate = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mcNumber = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mcRunningNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = 'pending'; // pending, completed, cancelled

    public function __construct()
    {
        $this->createdAt = \App\Service\TimezoneService::now();
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

    public function getConsultationDate(): ?\DateTimeInterface
    {
        return $this->consultationDate;
    }

    public function setConsultationDate(\DateTimeInterface $consultationDate): self
    {
        $this->consultationDate = $consultationDate;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function setDiagnosis(string $diagnosis): self
    {
        $this->diagnosis = $diagnosis;
        return $this;
    }

    public function getMedications(): ?string
    {
        return $this->medications;
    }

    public function setMedications(string $medications): self
    {
        $this->medications = $medications;
        return $this;
    }

    public function getSymptoms(): ?string
    {
        return $this->symptoms;
    }

    public function setSymptoms(?string $symptoms): self
    {
        $this->symptoms = $symptoms;
        return $this;
    }

    public function getTreatment(): ?string
    {
        return $this->treatment;
    }

    public function setTreatment(?string $treatment): self
    {
        $this->treatment = $treatment;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getFollowUpPlan(): ?string
    {
        return $this->followUpPlan;
    }

    public function setFollowUpPlan(?string $followUpPlan): self
    {
        $this->followUpPlan = $followUpPlan;
        return $this;
    }

    public function getConsultationFee(): ?string
    {
        return $this->consultationFee;
    }

    public function setConsultationFee(?string $consultationFee): self
    {
        $this->consultationFee = $consultationFee;
        $this->updateTotalAmount();
        return $this;
    }

    public function getMedicinesFee(): ?string
    {
        return $this->medicinesFee;
    }

    public function setMedicinesFee(?string $medicinesFee): self
    {
        $this->medicinesFee = $medicinesFee;
        $this->updateTotalAmount();
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    private function updateTotalAmount(): void
    {
        // Use bcadd for string decimal arithmetic
        $cf = $this->consultationFee !== null ? $this->consultationFee : '0.00';
        $mf = $this->medicinesFee !== null ? $this->medicinesFee : '0.00';
        $this->totalAmount = bcadd($cf, $mf, 2);
    }

    public function getIsPaid(): bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): self
    {
        $this->isPaid = $isPaid;
        if ($isPaid && $this->paidAt === null) {
            $this->paidAt = new \DateTime();
        }
        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): self
    {
        $this->paidAt = $paidAt;
        return $this;
    }
    
    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }
    
    public function setReceiptNumber(?string $receiptNumber): self
    {
        $this->receiptNumber = $receiptNumber;
        return $this;
    }
    
    public function getHasMedicalCertificate(): bool
    {
        return $this->hasMedicalCertificate;
    }
    
    public function setHasMedicalCertificate(bool $hasMedicalCertificate): self
    {
        $this->hasMedicalCertificate = $hasMedicalCertificate;
        return $this;
    }
    
    public function getMcStartDate(): ?\DateTimeInterface
    {
        return $this->mcStartDate;
    }
    
    public function setMcStartDate(?\DateTimeInterface $mcStartDate): self
    {
        $this->mcStartDate = $mcStartDate;
        return $this;
    }
    
    public function getMcEndDate(): ?\DateTimeInterface
    {
        return $this->mcEndDate;
    }
    
    public function setMcEndDate(?\DateTimeInterface $mcEndDate): self
    {
        $this->mcEndDate = $mcEndDate;
        return $this;
    }
    
    public function getMcNumber(): ?string
    {
        return $this->mcNumber;
    }
    
    public function setMcNumber(?string $mcNumber): self
    {
        $this->mcNumber = $mcNumber;
        return $this;
    }
    
    public function getMcRunningNumber(): ?string
    {
        return $this->mcRunningNumber;
    }
    
    public function setMcRunningNumber(?string $mcRunningNumber): self
    {
        $this->mcRunningNumber = $mcRunningNumber;
        return $this;
    }
    
    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }
}
