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
    public function login(Request $request): JsonResponse
    {
        // DEVELOPMENT MODE: Accept any username/password and return a hardcoded user
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? ($data['email'] ?? 'devuser');
        $user = [
            'email' => $username . '@example.com',
            'roles' => ['ROLE_USER'],
            'name' => $username,
            'username' => $username
        ];
        return $this->json([
            'user' => $user
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // DEVELOPMENT MODE: Accept any input as a valid user. Remove all validation and existence checks.
        $data = json_decode($request->getContent(), true);

        // Provide defaults if fields are missing
        $email = $data['email'] ?? (uniqid('user') . '@example.com');
        $name = $data['name'] ?? 'Dev User';
        $username = $data['username'] ?? ('user_' . uniqid());
        $password = $data['password'] ?? 'password';

        // Optionally, persist to database, but for development you can skip this
        // $user = new User();
        // $user->setEmail($email);
        // $user->setName($name);
        // $user->setUsername($username);
        // $user->setRoles(['ROLE_USER']);
        // $hashedPassword = $passwordHasher->hashPassword($user, $password);
        // $user->setPassword($hashedPassword);
        // $entityManager->persist($user);
        // $entityManager->flush();

        return $this->json([
            'message' => 'User registered successfully (development mode, any input accepted)',
            'user' => [
                'email' => $email,
                'name' => $name,
                'username' => $username
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on the firewall
    }
}
