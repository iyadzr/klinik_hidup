<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Find all settings grouped by category
     */
    public function findAllGroupedByCategory(): array
    {
        $settings = $this->findBy([], ['category' => 'ASC', 'settingKey' => 'ASC']);
        $grouped = [];
        
        foreach ($settings as $setting) {
            $category = $setting->getCategory() ?: 'general';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $setting;
        }
        
        return $grouped;
    }

    /**
     * Find setting by key
     */
    public function findByKey(string $key): ?Setting
    {
        return $this->findOneBy(['settingKey' => $key]);
    }

    /**
     * Find settings by category
     */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category], ['settingKey' => 'ASC']);
    }

    /**
     * Get setting value by key with default fallback
     */
    public function getSettingValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->findByKey($key);
        return $setting ? $setting->getTypedValue() : $default;
    }

    /**
     * Set setting value by key (creates if doesn't exist)
     */
    public function setSettingValue(string $key, mixed $value, string $category = 'general', string $valueType = 'string'): Setting
    {
        $setting = $this->findByKey($key);
        
        if (!$setting) {
            $setting = new Setting();
            $setting->setSettingKey($key);
            $setting->setCategory($category);
            $setting->setValueType($valueType);
            $this->getEntityManager()->persist($setting);
        }
        
        $setting->setTypedValue($value);
        $this->getEntityManager()->flush();
        
        return $setting;
    }
} 