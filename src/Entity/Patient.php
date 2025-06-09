<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
class Patient
{
    /**
     * @var Collection<int, \App\Entity\Consultation>
     */
    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: Consultation::class)]
    private Collection $consultations;

    public function __construct()
    {
        $this->consultations = new ArrayCollection();
    }

    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(\App\Entity\Consultation $consultation): self
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations[] = $consultation;
            $consultation->setPatient($this);
        }
        return $this;
    }

    public function removeConsultation(\App\Entity\Consultation $consultation): self
    {
        if ($this->consultations->removeElement($consultation)) {
            if ($consultation->getPatient() === $this) {
                $consultation->setPatient(null);
            }
        }
        return $this;
    }


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    private ?string $nric = null; // Singapore NRIC/FIN/IC


    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $phone = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $medicalHistory = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ClinicAssistant $registeredBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $preInformedIllness = null;

    public function getId(): ?int
    {
        return $this->id;
    }


    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getNric(): ?string
    {
        return $this->nric;
    }

    public function setNric(string $nric): self
    {
        $this->nric = $nric;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(\DateTimeInterface $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    public function getMedicalHistory(): ?string
    {
        return $this->medicalHistory;
    }

    public function setMedicalHistory(?string $medicalHistory): self
    {
        $this->medicalHistory = $medicalHistory;
        return $this;
    }

    public function getRegisteredBy(): ?ClinicAssistant
    {
        return $this->registeredBy;
    }

    public function setRegisteredBy(?ClinicAssistant $registeredBy): self
    {
        $this->registeredBy = $registeredBy;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getPreInformedIllness(): ?string
    {
        return $this->preInformedIllness;
    }

    public function setPreInformedIllness(?string $preInformedIllness): self
    {
        $this->preInformedIllness = $preInformedIllness;
        return $this;
    }
}
