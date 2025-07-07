<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Doctor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/api')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    #[Route('/users', name: 'api_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $users = $this->entityManager->getRepository(User::class)->findAll();
            $data = [];
            
            foreach ($users as $user) {
                $doctorProfile = $user->getDoctorProfile();
                $data[] = [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'allowedPages' => $user->getAllowedPages(),
                    'createdAt' => $user->getCreatedAt()?->format('Y-m-d\TH:i:s'),
                    'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d\TH:i:s'),
                    'doctorProfile' => $doctorProfile ? [
                        'id' => $doctorProfile->getId(),
                        'specialization' => $doctorProfile->getSpecialization(),
                        'licenseNumber' => $doctorProfile->getLicenseNumber(),
                        'phone' => $doctorProfile->getPhone()
                    ] : null
                ];
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error loading users: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to load users'], 500);
        }
    }

    #[Route('/users', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }

            // Validate required fields
            $requiredFields = ['name', 'username', 'email', 'password'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return new JsonResponse([
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                ], 400);
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['message' => 'Invalid email format'], 400);
            }

            // Check if username already exists
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy([
                'username' => $data['username']
            ]);
            if ($existingUser) {
                return new JsonResponse(['message' => 'Username already exists'], 400);
            }

            // Check if email already exists
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => $data['email']
            ]);
            if ($existingUser) {
                return new JsonResponse(['message' => 'Email already exists'], 400);
            }

            $user = new User();
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            
            // Hash the password for security
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Set roles
            $roles = $data['roles'] ?? ['ROLE_USER'];
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
            }
            $user->setRoles($roles);

            // Set active status
            $user->setIsActive($data['isActive'] ?? true);

            // Set allowed pages
            if (!isset($data['allowedPages']) && in_array('ROLE_DOCTOR', $roles)) {
                $user->setAllowedPages(['dashboard','queue','queue-display','consultations']);
            } else {
                $user->setAllowedPages($data['allowedPages'] ?? []);
            }

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], 400);
            }

            $this->entityManager->persist($user);

            // If user has ROLE_DOCTOR, automatically create doctor profile
            if (in_array('ROLE_DOCTOR', $roles)) {
                $doctor = new Doctor();
                $doctor->setName($user->getName());
                $doctor->setEmail($user->getEmail());
                $doctor->setPhone($data['phone'] ?? ''); // Use provided phone or empty
                $doctor->setSpecialization($data['specialization'] ?? 'General Practice (GP)');
                if (isset($data['licenseNumber'])) {
                    $doctor->setLicenseNumber($data['licenseNumber']);
                }
                $doctor->setWorkingHours($data['workingHours'] ?? []);
                
                // Link them together
                $doctor->setUser($user);
                $this->entityManager->persist($doctor);
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'User created successfully' . (in_array('ROLE_DOCTOR', $roles) ? ' with doctor profile' : ''),
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'allowedPages' => $user->getAllowedPages(),
                    'doctorProfileCreated' => in_array('ROLE_DOCTOR', $roles)
                ]
            ], 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating user: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }

    #[Route('/users/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            return new JsonResponse([
                'id' => $user->getId(),
                'name' => $user->getName(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isActive' => $user->isActive(),
                'allowedPages' => $user->getAllowedPages(),
                'profileImage' => $user->getProfileImage(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d\TH:i:s'),
                'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d\TH:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error showing user: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to load user'], 500);
        }
    }

    #[Route('/users/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }

            // Update name
            if (isset($data['name'])) {
                $user->setName($data['name']);
            }

            // Update username
            if (isset($data['username'])) {
                // Check if username is already used by another user
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['username' => $data['username']]);
                    
                if ($existingUser && $existingUser->getId() !== $id) {
                    return new JsonResponse(['message' => 'Username already exists'], 400);
                }
                
                $user->setUsername($data['username']);
            }

            // Update email
            if (isset($data['email'])) {
                // Validate email format
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return new JsonResponse(['message' => 'Invalid email format'], 400);
                }

                // Check if email is already used by another user
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $data['email']]);
                    
                if ($existingUser && $existingUser->getId() !== $id) {
                    return new JsonResponse(['message' => 'Email already exists'], 400);
                }
                
                $user->setEmail($data['email']);
            }

            // Update password (if provided) - hash it for security
            if (isset($data['password']) && !empty(trim($data['password']))) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }

            // Update roles
            if (isset($data['roles'])) {
                $roles = $data['roles'];
                if (!in_array('ROLE_USER', $roles)) {
                    $roles[] = 'ROLE_USER';
                }
                $user->setRoles($roles);
            }

            // Update active status
            if (isset($data['isActive'])) {
                $user->setIsActive($data['isActive']);
            }

            // Update allowed pages
            if (isset($data['allowedPages'])) {
                $user->setAllowedPages($data['allowedPages']);
            }

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], 400);
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'allowedPages' => $user->getAllowedPages(),
                    'profileImage' => $user->getProfileImage()
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error updating user: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }

    #[Route('/users/{id}/permissions', name: 'api_users_permissions', methods: ['PUT'])]
    public function updatePermissions(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }

            // Update allowed pages
            if (isset($data['allowedPages'])) {
                $user->setAllowedPages($data['allowedPages']);
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Permissions updated successfully',
                'allowedPages' => $user->getAllowedPages()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error updating permissions: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }

    #[Route('/users/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $user = $this->entityManager->getRepository(User::class)->find($id);
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 404);
            }

            // Prevent deletion of super admin users
            if ($user->hasRole('ROLE_SUPER_ADMIN')) {
                return new JsonResponse([
                    'error' => 'Cannot delete super admin users',
                    'message' => 'Super admin users cannot be deleted for security reasons'
                ], 403);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'User deleted successfully']);
            
        } catch (\Exception $e) {
            $this->logger->error('Error deleting user: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }

    #[Route('/profile', name: 'app_user_profile', methods: ['GET'])]
    public function getProfile(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user from JWT token
            $user = $this->getUser();
            
            if (!$user || !($user instanceof \App\Entity\User)) {
                return new JsonResponse(['error' => 'Not authenticated'], 401);
            }

            // Get doctor profile if user is a doctor
            $doctorProfile = $user->getDoctorProfile();

            return new JsonResponse([
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'allowedPages' => $user->getAllowedPages(),
                    'profileImage' => $user->getProfileImage(),
                    'createdAt' => $user->getCreatedAt()?->format('Y-m-d\TH:i:s'),
                    'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d\TH:i:s'),
                    'doctorProfile' => $doctorProfile ? [
                        'id' => $doctorProfile->getId(),
                        'specialization' => $doctorProfile->getSpecialization(),
                        'licenseNumber' => $doctorProfile->getLicenseNumber(),
                        'phone' => $doctorProfile->getPhone(),
                        'workingHours' => $doctorProfile->getWorkingHours()
                    ] : null
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting user profile: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to load profile'], 500);
        }
    }

    #[Route('/profile', name: 'app_user_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user from JWT token
            $user = $this->getUser();
            
            if (!$user || !($user instanceof \App\Entity\User)) {
                return new JsonResponse(['error' => 'Not authenticated'], 401);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }

            // Update allowed fields
            if (isset($data['name']) && !empty(trim($data['name']))) {
                $user->setName(trim($data['name']));
            }

            if (isset($data['email']) && !empty(trim($data['email']))) {
                // Validate email format
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return new JsonResponse(['message' => 'Invalid email format'], 400);
                }

                // Check if email already exists for another user
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy([
                    'email' => $data['email']
                ]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    return new JsonResponse(['message' => 'Email already exists'], 400);
                }

                $user->setEmail(trim($data['email']));
            }

            // Update password if provided - hash it for security
            if (isset($data['password']) && !empty(trim($data['password']))) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, trim($data['password']));
                $user->setPassword($hashedPassword);
            }

            // Update doctor profile if user is a doctor and data is provided
            if ($user->hasRole('ROLE_DOCTOR') && isset($data['doctorProfile'])) {
                $doctorProfile = $user->getDoctorProfile();
                if ($doctorProfile) {
                    $doctorData = $data['doctorProfile'];
                    
                    if (isset($doctorData['specialization'])) {
                        $doctorProfile->setSpecialization($doctorData['specialization']);
                    }
                    
                    if (isset($doctorData['phone'])) {
                        $doctorProfile->setPhone($doctorData['phone']);
                    }
                    
                    if (isset($doctorData['licenseNumber'])) {
                        $doctorProfile->setLicenseNumber($doctorData['licenseNumber']);
                    }
                    
                    if (isset($doctorData['workingHours'])) {
                        $doctorProfile->setWorkingHours($doctorData['workingHours']);
                    }
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                    'profileImage' => $user->getProfileImage()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error updating user profile: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to update profile'], 500);
        }
    }

    #[Route('/users/profile-image', name: 'api_users_profile_image_upload', methods: ['POST'])]
    public function uploadProfileImage(Request $request): JsonResponse
    {
        try {
            // Get current user from token/auth
            $user = $this->getUser();
            $this->logger->info('Upload attempt - User from getUser(): ' . ($user ? get_class($user) : 'null'));
            
            if (!$user || !($user instanceof \App\Entity\User)) {
                $this->logger->error('Authentication failed - user not found or wrong type');
                return new JsonResponse(['error' => 'Not authenticated'], 401);
            }
            $userId = $user->getId();
            $this->logger->info('User authenticated successfully - ID: ' . $userId);
            
            $uploadedFile = $request->files->get('profileImage');
            
            if (!$uploadedFile) {
                $this->logger->error('No file uploaded');
                return new JsonResponse(['error' => 'No file uploaded'], 400);
            }

            $this->logger->info('File uploaded - Original name: ' . $uploadedFile->getClientOriginalName() . ', Size: ' . $uploadedFile->getSize());

            // Validate file
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $actualMimeType = $uploadedFile->getMimeType();
            $this->logger->info('File MIME type: ' . $actualMimeType);
            
            if (!in_array($actualMimeType, $allowedMimes)) {
                return new JsonResponse(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'], 400);
            }

            // Validate file size (5MB max)
            if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
                return new JsonResponse(['error' => 'File too large. Maximum size is 5MB.'], 400);
            }

            // Create uploads directory if it doesn't exist
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
            if (!is_dir($uploadsDir)) {
                $this->logger->info('Creating uploads directory: ' . $uploadsDir);
                mkdir($uploadsDir, 0755, true);
            }

            // Generate unique filename
            $extension = $uploadedFile->guessExtension();
            $filename = 'profile_' . $userId . '_' . uniqid() . '.' . $extension;
            $this->logger->info('Generated filename: ' . $filename);
            
            // Remove old profile image if exists
            if ($user->getProfileImage()) {
                $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $user->getProfileImage();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                    $this->logger->info('Removed old profile image: ' . $oldImagePath);
                }
            }

            // Move uploaded file
            $uploadedFile->move($uploadsDir, $filename);
            $this->logger->info('File moved successfully to: ' . $uploadsDir . '/' . $filename);

            // Update user profile
            $profileImageUrl = '/uploads/profiles/' . $filename;
            $user->setProfileImage($profileImageUrl);
            $this->entityManager->flush();
            $this->logger->info('User profile updated with new image URL: ' . $profileImageUrl);

            return new JsonResponse([
                'message' => 'Profile image uploaded successfully',
                'profileImageUrl' => $profileImageUrl
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error uploading profile image: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Failed to upload image: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/users/profile-image', name: 'api_users_profile_image_delete', methods: ['DELETE'])]
    public function removeProfileImage(Request $request): JsonResponse
    {
        try {
            // Get current user from token/auth
            $user = $this->getUser();
            if (!$user || !($user instanceof \App\Entity\User)) {
                return new JsonResponse(['error' => 'Not authenticated'], 401);
            }

            // Remove old profile image file if exists
            if ($user->getProfileImage()) {
                $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $user->getProfileImage();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                    $this->logger->info('Removed profile image file: ' . $oldImagePath);
                }
            }

            // Update user profile
            $user->setProfileImage(null);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Profile image removed successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error removing profile image: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to remove image'], 500);
        }
    }
} 