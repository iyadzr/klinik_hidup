<?php

namespace App\Controller;

use App\Entity\User;
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
                $data[] = [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'allowedPages' => $user->getAllowedPages(),
                    'createdAt' => $user->getCreatedAt()?->format('Y-m-d\TH:i:s'),
                    'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d\TH:i:s')
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
            
            // Hash the password
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
            $user->setAllowedPages($data['allowedPages'] ?? []);

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string) $errors], 400);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'isActive' => $user->isActive(),
                    'allowedPages' => $user->getAllowedPages()
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

            // Update password (if provided)
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
                    'allowedPages' => $user->getAllowedPages()
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
        // For development mode, return mock data based on localStorage
        // In production, you would get the current authenticated user
        return new JsonResponse([
            'user' => [
                'id' => 1,
                'name' => 'Dev User',
                'email' => 'dev@example.com',
                'username' => 'devuser'
            ]
        ]);
    }

    #[Route('/profile', name: 'app_user_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }

            $this->logger->info('Profile update request received', ['data' => $data]);

            // Validate required fields
            $requiredFields = ['name', 'email', 'username'];
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
                return new JsonResponse([
                    'message' => 'Invalid email format'
                ], 400);
            }

            // DEVELOPMENT MODE: For now, just return success without actually updating
            // In production, you would:
            // 1. Get the current authenticated user
            // 2. Check if email/username already exists for other users
            // 3. Update user fields
            // 4. Hash and update password if provided
            // 5. Persist changes

            // Mock response for development
            $updatedUser = [
                'id' => 1,
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username']
            ];

            $this->logger->info('Profile updated successfully (development mode)', ['user' => $updatedUser]);

            return new JsonResponse([
                'message' => 'Profile updated successfully',
                'user' => $updatedUser
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error updating profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
} 