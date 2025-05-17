<?php

namespace App\Controller;

use App\Entity\ClinicAssistant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class ClinicAssistantController extends AbstractController
{
    #[Route('/api/clinic-assistants', name: 'app_clinic_assistants_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $assistants = $entityManager->getRepository(ClinicAssistant::class)->findAll();
        $data = [];
        
        foreach ($assistants as $assistant) {
            $data[] = [
                'id' => $assistant->getId(),
                'name' => $assistant->getName(),
                'email' => $assistant->getEmail(),
                'phone' => $assistant->getPhone(),
                'username' => $assistant->getUsername()
            ];
        }
        
        return new JsonResponse($data);
    }

    #[Route('/api/clinic-assistants', name: 'app_clinic_assistants_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }
            
            // Validate required fields
            $requiredFields = ['name', 'email', 'phone', 'username', 'password'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty(trim($data[$field]))) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return new JsonResponse([
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                ], 400);
            }
            
            // Check if username already exists
            $existingAssistant = $entityManager->getRepository(ClinicAssistant::class)
                ->findOneBy(['username' => $data['username']]);
                
            if ($existingAssistant) {
                return new JsonResponse(['message' => 'Username already exists'], 400);
            }
            
            // Check if email already exists
            $existingAssistant = $entityManager->getRepository(ClinicAssistant::class)
                ->findOneBy(['email' => $data['email']]);
                
            if ($existingAssistant) {
                return new JsonResponse(['message' => 'Email already exists'], 400);
            }
            
            $assistant = new ClinicAssistant();
            $assistant->setName($data['name']);
            $assistant->setEmail($data['email']);
            $assistant->setPhone($data['phone']);
            $assistant->setUsername($data['username']);
            $assistant->setPassword($data['password']); // Note: You might want to hash this password
            
            $entityManager->persist($assistant);
            $entityManager->flush();
            
            return new JsonResponse([
                'message' => 'Clinic assistant created successfully',
                'assistant' => [
                    'id' => $assistant->getId(),
                    'name' => $assistant->getName(),
                    'email' => $assistant->getEmail(),
                    'phone' => $assistant->getPhone(),
                    'username' => $assistant->getUsername()
                ]
            ], 201);
            
        } catch (\Exception $e) {
            $logger->error('Error creating clinic assistant: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }

    #[Route('/api/clinic-assistants/{id}', name: 'app_clinic_assistants_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Invalid JSON'], 400);
            }
            
            $assistant = $entityManager->getRepository(ClinicAssistant::class)->find($id);
            
            if (!$assistant) {
                return new JsonResponse(['message' => 'Clinic assistant not found'], 404);
            }
            
            if (isset($data['name'])) {
                $assistant->setName($data['name']);
            }
            
            if (isset($data['email'])) {
                // Check if email is already used by another assistant
                $existingAssistant = $entityManager->getRepository(ClinicAssistant::class)
                    ->findOneBy(['email' => $data['email']]);
                    
                if ($existingAssistant && $existingAssistant->getId() !== $id) {
                    return new JsonResponse(['message' => 'Email already exists'], 400);
                }
                
                $assistant->setEmail($data['email']);
            }
            
            if (isset($data['phone'])) {
                $assistant->setPhone($data['phone']);
            }
            
            if (isset($data['username'])) {
                // Check if username is already used by another assistant
                $existingAssistant = $entityManager->getRepository(ClinicAssistant::class)
                    ->findOneBy(['username' => $data['username']]);
                    
                if ($existingAssistant && $existingAssistant->getId() !== $id) {
                    return new JsonResponse(['message' => 'Username already exists'], 400);
                }
                
                $assistant->setUsername($data['username']);
            }
            
            if (isset($data['password'])) {
                $assistant->setPassword($data['password']); // Note: You might want to hash this password
            }
            
            $entityManager->flush();
            
            return new JsonResponse([
                'message' => 'Clinic assistant updated successfully',
                'assistant' => [
                    'id' => $assistant->getId(),
                    'name' => $assistant->getName(),
                    'email' => $assistant->getEmail(),
                    'phone' => $assistant->getPhone(),
                    'username' => $assistant->getUsername()
                ]
            ]);
            
        } catch (\Exception $e) {
            $logger->error('Error updating clinic assistant: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal server error'], 500);
        }
    }
}
