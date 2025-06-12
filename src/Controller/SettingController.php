<?php

namespace App\Controller;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/settings')]
class SettingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingRepository $settingRepository,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    #[Route('', name: 'api_settings_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $groupedSettings = $this->settingRepository->findAllGroupedByCategory();
            $data = [];
            
            foreach ($groupedSettings as $category => $settings) {
                $data[$category] = [];
                foreach ($settings as $setting) {
                    $data[$category][] = [
                        'id' => $setting->getId(),
                        'key' => $setting->getSettingKey(),
                        'value' => $setting->getTypedValue(),
                        'rawValue' => $setting->getSettingValue(),
                        'type' => $setting->getValueType(),
                        'description' => $setting->getDescription(),
                        'isSystem' => $setting->isSystem(),
                        'updatedAt' => $setting->getUpdatedAt()?->format('Y-m-d\TH:i:s')
                    ];
                }
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error loading settings: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to load settings'], 500);
        }
    }

    #[Route('/categories', name: 'api_settings_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        try {
            $categories = [
                'clinic' => [
                    'name' => 'Clinic Information',
                    'icon' => 'fas fa-hospital',
                    'description' => 'Basic clinic details and contact information'
                ],
                'business' => [
                    'name' => 'Business Settings',
                    'icon' => 'fas fa-business-time',
                    'description' => 'Operating hours, scheduling, and business rules'
                ],
                'financial' => [
                    'name' => 'Financial Settings',
                    'icon' => 'fas fa-dollar-sign',
                    'description' => 'Currency, pricing, and payment configurations'
                ],
                'notifications' => [
                    'name' => 'Notifications',
                    'icon' => 'fas fa-bell',
                    'description' => 'Email templates and notification settings'
                ],
                'security' => [
                    'name' => 'Security',
                    'icon' => 'fas fa-shield-alt',
                    'description' => 'Security policies and access controls'
                ],
                'system' => [
                    'name' => 'System',
                    'icon' => 'fas fa-cogs',
                    'description' => 'System maintenance and backup settings'
                ]
            ];
            
            return new JsonResponse($categories);
        } catch (\Exception $e) {
            $this->logger->error('Error loading categories: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to load categories'], 500);
        }
    }

    #[Route('/bulk-update', name: 'api_settings_bulk_update', methods: ['POST'])]
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['settings'])) {
                return new JsonResponse(['message' => 'Invalid JSON or missing settings'], 400);
            }

            $updatedSettings = [];
            
            foreach ($data['settings'] as $settingData) {
                if (!isset($settingData['key'])) {
                    continue;
                }
                
                $setting = $this->settingRepository->findByKey($settingData['key']);
                
                if (!$setting) {
                    // Create new setting
                    $setting = new Setting();
                    $setting->setSettingKey($settingData['key']);
                    $setting->setCategory($settingData['category'] ?? 'general');
                    $setting->setValueType($settingData['type'] ?? 'string');
                    $setting->setDescription($settingData['description'] ?? null);
                    $setting->setIsSystem($settingData['isSystem'] ?? false);
                    $this->entityManager->persist($setting);
                }
                
                // Update value
                if (isset($settingData['value'])) {
                    $setting->setTypedValue($settingData['value']);
                }
                
                $errors = $this->validator->validate($setting);
                if (count($errors) > 0) {
                    return new JsonResponse([
                        'error' => 'Validation failed for setting: ' . $setting->getSettingKey(),
                        'details' => (string) $errors
                    ], 400);
                }
                
                $updatedSettings[] = $setting->getSettingKey();
            }
            
            $this->entityManager->flush();
            
            return new JsonResponse([
                'message' => 'Settings updated successfully',
                'updated' => $updatedSettings
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error updating settings: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }

    #[Route('/{key}', name: 'api_settings_show', methods: ['GET'])]
    public function show(string $key): JsonResponse
    {
        try {
            $setting = $this->settingRepository->findByKey($key);
            
            if (!$setting) {
                return new JsonResponse(['error' => 'Setting not found'], 404);
            }

            return new JsonResponse([
                'id' => $setting->getId(),
                'key' => $setting->getSettingKey(),
                'value' => $setting->getTypedValue(),
                'rawValue' => $setting->getSettingValue(),
                'type' => $setting->getValueType(),
                'category' => $setting->getCategory(),
                'description' => $setting->getDescription(),
                'isSystem' => $setting->isSystem(),
                'createdAt' => $setting->getCreatedAt()?->format('Y-m-d\TH:i:s'),
                'updatedAt' => $setting->getUpdatedAt()?->format('Y-m-d\TH:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error showing setting: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to load setting'], 500);
        }
    }

    #[Route('/{key}', name: 'api_settings_update', methods: ['PUT'])]
    public function update(string $key, Request $request): JsonResponse
    {
        try {
            $setting = $this->settingRepository->findByKey($key);
            
            if (!$setting) {
                return new JsonResponse(['error' => 'Setting not found'], 404);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }

            // Update value
            if (isset($data['value'])) {
                $setting->setTypedValue($data['value']);
            }
            
            // Update description if provided
            if (isset($data['description'])) {
                $setting->setDescription($data['description']);
            }

            $errors = $this->validator->validate($setting);
            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], 400);
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Setting updated successfully',
                'setting' => [
                    'key' => $setting->getSettingKey(),
                    'value' => $setting->getTypedValue(),
                    'type' => $setting->getValueType()
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error updating setting: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }

    #[Route('/initialize', name: 'api_settings_initialize', methods: ['POST'])]
    public function initializeDefaultSettings(): JsonResponse
    {
        try {
            $defaultSettings = $this->getDefaultSettings();
            $created = 0;
            
            foreach ($defaultSettings as $settingData) {
                $existing = $this->settingRepository->findByKey($settingData['key']);
                if (!$existing) {
                    $setting = new Setting();
                    $setting->setSettingKey($settingData['key']);
                    $setting->setCategory($settingData['category']);
                    $setting->setValueType($settingData['type']);
                    $setting->setDescription($settingData['description']);
                    $setting->setIsSystem(true);
                    $setting->setTypedValue($settingData['defaultValue']);
                    
                    $this->entityManager->persist($setting);
                    $created++;
                }
            }
            
            $this->entityManager->flush();
            
            return new JsonResponse([
                'message' => 'Default settings initialized',
                'created' => $created
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error initializing settings: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }

    private function getDefaultSettings(): array
    {
        return [
            // Clinic Information
            [
                'key' => 'clinic.name',
                'category' => 'clinic',
                'type' => 'string',
                'defaultValue' => 'Klinik Hidup Sihat',
                'description' => 'Name of the clinic'
            ],
            [
                'key' => 'clinic.address',
                'category' => 'clinic',
                'type' => 'string',
                'defaultValue' => '',
                'description' => 'Physical address of the clinic'
            ],
            [
                'key' => 'clinic.phone',
                'category' => 'clinic',
                'type' => 'string',
                'defaultValue' => '',
                'description' => 'Main contact phone number'
            ],
            [
                'key' => 'clinic.email',
                'category' => 'clinic',
                'type' => 'email',
                'defaultValue' => '',
                'description' => 'Main contact email address'
            ],
            [
                'key' => 'clinic.website',
                'category' => 'clinic',
                'type' => 'url',
                'defaultValue' => '',
                'description' => 'Clinic website URL'
            ],
            
            // Business Settings
            [
                'key' => 'business.timezone',
                'category' => 'business',
                'type' => 'string',
                'defaultValue' => 'Asia/Kuala_Lumpur',
                'description' => 'Default timezone for the clinic'
            ],
            [
                'key' => 'business.operating_hours',
                'category' => 'business',
                'type' => 'json',
                'defaultValue' => [
                    'monday' => ['start' => '09:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                    'thursday' => ['start' => '09:00', 'end' => '17:00'],
                    'friday' => ['start' => '09:00', 'end' => '17:00'],
                    'saturday' => ['start' => '09:00', 'end' => '13:00'],
                    'sunday' => ['closed' => true]
                ],
                'description' => 'Weekly operating hours'
            ],
            [
                'key' => 'business.appointment_duration',
                'category' => 'business',
                'type' => 'number',
                'defaultValue' => 30,
                'description' => 'Default appointment duration in minutes'
            ],
            
            // Financial Settings
            [
                'key' => 'financial.currency',
                'category' => 'financial',
                'type' => 'string',
                'defaultValue' => 'MYR',
                'description' => 'Default currency code'
            ],
            [
                'key' => 'financial.consultation_fee',
                'category' => 'financial',
                'type' => 'number',
                'defaultValue' => 50.00,
                'description' => 'Default consultation fee'
            ],
            [
                'key' => 'financial.payment_methods',
                'category' => 'financial',
                'type' => 'json',
                'defaultValue' => ['cash', 'card'],
                'description' => 'Accepted payment methods'
            ],
            
            // Security Settings
            [
                'key' => 'security.session_timeout',
                'category' => 'security',
                'type' => 'number',
                'defaultValue' => 480,
                'description' => 'Session timeout in minutes'
            ],
            [
                'key' => 'security.password_min_length',
                'category' => 'security',
                'type' => 'number',
                'defaultValue' => 8,
                'description' => 'Minimum password length'
            ],
            [
                'key' => 'security.require_password_change',
                'category' => 'security',
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'Require users to change password on first login'
            ],
            
            // System Settings
            [
                'key' => 'system.backup_enabled',
                'category' => 'system',
                'type' => 'boolean',
                'defaultValue' => true,
                'description' => 'Enable automatic backups'
            ],
            [
                'key' => 'system.backup_frequency',
                'category' => 'system',
                'type' => 'string',
                'defaultValue' => 'daily',
                'description' => 'Backup frequency (daily, weekly, monthly)'
            ],
            [
                'key' => 'system.maintenance_mode',
                'category' => 'system',
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'Enable maintenance mode'
            ]
        ];
    }
} 