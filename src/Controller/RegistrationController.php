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

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            // Get raw content and request info
            $content = $request->getContent();
            $contentType = $request->headers->get('Content-Type');
            
            $logger->error('Request info:', [
                'content' => $content,
                'content_type' => $contentType,
                'method' => $request->getMethod(),
                'headers' => $request->headers->all()
            ]);

            // Parse request data based on content type
            if (str_contains($contentType ?? '', 'application/json')) {
                $data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $logger->error('JSON decode error:', [
                        'error' => json_last_error_msg(),
                        'content' => $content
                    ]);
                    return new JsonResponse([
                        'message' => 'Invalid JSON format: ' . json_last_error_msg()
                    ], 400);
                }
            } else {
                $data = $request->request->all();
                $logger->error('Form data:', ['data' => $data]);
            }

            // Check if we have any data
            if (empty($data)) {
                $logger->error('No data received');
                return new JsonResponse([
                    'message' => 'No data provided'
                ], 400);
            }

            $logger->error('Parsed request data:', ['data' => $data]);

            // Validate required fields
            $requiredFields = ['name', 'email', 'username', 'password'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                $logger->error('Missing fields:', [
                    'missing' => $missingFields,
                    'provided' => array_keys($data)
                ]);
                return new JsonResponse([
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                ], 400);
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $logger->error('Invalid email format', ['email' => $data['email']]);
                return new JsonResponse([
                    'message' => 'Invalid email format'
                ], 400);
            }

            // Check if username already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'username' => $data['username']
            ]);
            if ($existingUser) {
                $logger->warning('Username already exists', ['username' => $data['username']]);
                return new JsonResponse([
                    'message' => 'Username already exists'
                ], 400);
            }

            // Check if email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $data['email']
            ]);
            if ($existingUser) {
                $logger->warning('Email already exists', ['email' => $data['email']]);
                return new JsonResponse([
                    'message' => 'Email already exists'
                ], 400);
            }

            $user = new User();
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Set default role
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            $logger->info('User registered successfully', ['id' => $user->getId()]);

            return new JsonResponse([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername()
                ]
            ], 201);
        } catch (\Exception $e) {
            $logger->error('Error in registration process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'message' => 'Internal server error during registration: ' . $e->getMessage()
            ], 500);
        }
    }
}
