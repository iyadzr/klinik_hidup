<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[Route('/api')]
class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->json([
                'error' => 'Invalid credentials',
                'message' => 'Email and password are required.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Find user by email or username
        $user = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.email = :identifier OR u.username = :identifier')
            ->setParameter('identifier', $email)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            return $this->json([
                'error' => 'Invalid credentials',
                'message' => 'User not found. Please contact administrator for account registration.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user is active
        if (!$user->isActive()) {
            return $this->json([
                'error' => 'Account disabled',
                'message' => 'Your account has been disabled. Please contact administrator.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verify password (temporarily disabled password hashing for testing)
        if ($user->getPassword() !== $password) {
            return $this->json([
                'error' => 'Invalid credentials',
                'message' => 'Incorrect password. Please try again.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Create a proper session token
        $token = base64_encode(random_bytes(32));
        
        // Store session in session storage or cache (simplified approach)
        $request->getSession()->set('auth_token', $token);
        $request->getSession()->set('user_id', $user->getId());

        // Return token and user data
        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'allowedPages' => $user->getAllowedPages()
            ]
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
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
                return $this->json([
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['message' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
            }

            // Check if username already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'username' => $data['username']
            ]);
            if ($existingUser) {
                return $this->json(['message' => 'Username already exists'], Response::HTTP_BAD_REQUEST);
            }

            // Check if email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $data['email']
            ]);
            if ($existingUser) {
                return $this->json(['message' => 'Email already exists'], Response::HTTP_BAD_REQUEST);
            }

            // Create new user
            $user = new User();
            $user->setEmail($data['email']);
            $user->setName($data['name']);
            $user->setUsername($data['username']);
            $user->setRoles(['ROLE_USER']);
            $user->setIsActive(true);
            
            // Store password as plain text (temporarily for testing)
            $user->setPassword($data['password']);
            
            // Persist to database
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'username' => $user->getUsername()
                ]
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Internal server error during registration: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on the firewall
    }
}
