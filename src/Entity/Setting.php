<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    private ?string $settingKey = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $settingValue = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $valueType = 'string'; // string, number, boolean, json, email, url

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isSystem = false; // System settings cannot be deleted

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): ?string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): static
    {
        $this->settingKey = $settingKey;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSettingValue(): ?string
    {
        return $this->settingValue;
    }

    public function setSettingValue(?string $settingValue): static
    {
        $this->settingValue = $settingValue;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getValueType(): ?string
    {
        return $this->valueType;
    }

    public function setValueType(string $valueType): static
    {
        $this->valueType = $valueType;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): static
    {
        $this->isSystem = $isSystem;
        return $this;
    }

    /**
     * Get the typed value based on valueType
     */
    public function getTypedValue(): mixed
    {
        if ($this->settingValue === null) {
            return null;
        }

        return match ($this->valueType) {
            'boolean' => filter_var($this->settingValue, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->settingValue) ? (float) $this->settingValue : 0,
            'json' => json_decode($this->settingValue, true),
            default => $this->settingValue
        };
    }

    /**
     * Set value with automatic type conversion
     */
    public function setTypedValue(mixed $value): static
    {
        if ($value === null) {
            $this->settingValue = null;
            return $this;
        }

        $this->settingValue = match ($this->valueType) {
            'boolean' => $value ? '1' : '0',
            'number' => (string) $value,
            'json' => json_encode($value),
            default => (string) $value
        };

        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
} 