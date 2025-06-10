<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api')]
class UserController extends AbstractController
{
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
    public function updateProfile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }

            $logger->info('Profile update request received', ['data' => $data]);

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

            $logger->info('Profile updated successfully (development mode)', ['user' => $updatedUser]);

            return new JsonResponse([
                'message' => 'Profile updated successfully',
                'user' => $updatedUser
            ]);

        } catch (\Exception $e) {
            $logger->error('Error updating profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
} 