<?php

namespace App\Controller;

use App\Entity\Doctor;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/doctors')]
class DoctorController extends AbstractController
{
    #[Route('', name: 'app_doctor_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $doctors = $entityManager->getRepository(Doctor::class)->findAll();
        $result = array_map(function($doctor) {
            $user = $doctor->getUser();
            return [
                'id' => $doctor->getId(),
                'displayName' => $doctor->getName(),
                'name' => $doctor->getName(),
                'email' => $doctor->getEmail(),
                'phone' => $doctor->getPhone(),
                'specialization' => $doctor->getSpecialization(),
                'licenseNumber' => $doctor->getLicenseNumber(),
                'workingHours' => $doctor->getWorkingHours(),
                'hasUserAccount' => $user !== null,
                'userId' => $user ? $user->getId() : null,
                'userRoles' => $user ? $user->getRoles() : [],
                'isActive' => $user ? $user->isActive() : false
            ];
        }, $doctors);
        return $this->json($result);
    }

    #[Route('/count', name: 'app_doctor_count', methods: ['GET'])]
    public function count(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Use createQueryBuilder for more direct control
            $queryBuilder = $entityManager->createQueryBuilder()
                ->select('COUNT(d.id)')
                ->from(Doctor::class, 'd');
            
            $count = $queryBuilder->getQuery()->getSingleScalarResult();
            
            return $this->json(['count' => $count]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error in doctor count: ' . $e->getMessage());
            
            // Return error response with details
            return $this->json([
                'error' => 'Failed to count doctors',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'app_doctor_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Check if email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return $this->json([
                    'error' => 'Email already exists',
                    'message' => 'A user with this email already exists in the system'
                ], Response::HTTP_CONFLICT);
            }

            // Create user account first
            $user = new User();
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setUsername($data['username'] ?? strtolower(str_replace(' ', '.', $data['name'])));
            $user->setRoles(['ROLE_USER', 'ROLE_DOCTOR']);
            $user->setIsActive(true);
            $user->setAllowedPages(['dashboard', 'consultations', 'patients', 'queue']);

            // Set default password or provided password
            $password = $data['password'] ?? 'temp123456';
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Check if username already exists and make it unique
            $baseUsername = $user->getUsername();
            $counter = 1;
            while ($entityManager->getRepository(User::class)->findOneBy(['username' => $user->getUsername()])) {
                $user->setUsername($baseUsername . '.' . $counter);
                $counter++;
            }

            // Create doctor profile
            $doctor = new Doctor();
            $doctor->setName($data['name']);
            $doctor->setEmail($data['email']);
            $doctor->setPhone($data['phone']);
            $doctor->setSpecialization($data['specialization']);
            if (isset($data['licenseNumber'])) {
                $doctor->setLicenseNumber($data['licenseNumber']);
            }
            if (isset($data['workingHours'])) {
                $doctor->setWorkingHours($data['workingHours']);
            }

            // Link them together
            $doctor->setUser($user);

            $entityManager->persist($user);
            $entityManager->persist($doctor);
            $entityManager->flush();

            return $this->json([
                'message' => 'Doctor and user account created successfully',
                'doctor' => [
                    'id' => $doctor->getId(),
                    'name' => $doctor->getName(),
                    'email' => $doctor->getEmail(),
                    'phone' => $doctor->getPhone(),
                    'specialization' => $doctor->getSpecialization(),
                    'displayName' => $doctor->getName(),
                    'hasUserAccount' => true,
                    'userId' => $user->getId(),
                    'username' => $user->getUsername(),
                    'temporaryPassword' => $password
                ]
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create doctor',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_doctor_show', methods: ['GET'])]
    public function show(Doctor $doctor): JsonResponse
    {
        return $this->json([
            'doctor' => [
                'id' => $doctor->getId(),
                'name' => $doctor->getName(),
                
                'email' => $doctor->getEmail(),
                'phone' => $doctor->getPhone(),
                'specialization' => $doctor->getSpecialization(),
                'displayName' => $doctor->getName(),
                'licenseNumber' => $doctor->getLicenseNumber(),
                'workingHours' => $doctor->getWorkingHours(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_update', methods: ['PUT'])]
    public function update(Request $request, Doctor $doctor, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $doctor->setName($data['name']);
        }
        if (isset($data['email'])) {
            $doctor->setEmail($data['email']);
        }
        if (isset($data['phone'])) {
            $doctor->setPhone($data['phone']);
        }
        if (isset($data['specialization'])) {
            $doctor->setSpecialization($data['specialization']);
        }
        if (isset($data['licenseNumber'])) {
            $doctor->setLicenseNumber($data['licenseNumber']);
        }
        if (isset($data['workingHours'])) {
            $doctor->setWorkingHours($data['workingHours']);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Doctor updated successfully',
            'doctor' => [
                'id' => $doctor->getId(),
                'name' => $doctor->getName(),
                
                'email' => $doctor->getEmail(),
                'specialization' => $doctor->getSpecialization(),
'displayName' => $doctor->getName(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_delete', methods: ['DELETE'])]
    public function delete(Doctor $doctor, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Check if doctor has any consultations
            $consultationCount = $entityManager->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from('App\Entity\Consultation', 'c')
                ->where('c.doctor = :doctor')
                ->setParameter('doctor', $doctor)
                ->getQuery()
                ->getSingleScalarResult();
            
            if ($consultationCount > 0) {
                return $this->json([
                    'error' => 'Cannot delete doctor',
                    'message' => "This doctor has {$consultationCount} consultation(s) and cannot be deleted. Please reassign or remove consultations first."
                ], Response::HTTP_CONFLICT);
            }
            
            // Check if doctor has any queue entries
            $queueCount = $entityManager->createQueryBuilder()
                ->select('COUNT(q.id)')
                ->from('App\Entity\Queue', 'q')
                ->where('q.doctor = :doctor')
                ->setParameter('doctor', $doctor)
                ->getQuery()
                ->getSingleScalarResult();
            
            if ($queueCount > 0) {
                return $this->json([
                    'error' => 'Cannot delete doctor',
                    'message' => "This doctor has {$queueCount} queue entry(ies) and cannot be deleted. Please reassign or remove queue entries first."
                ], Response::HTTP_CONFLICT);
            }
            
            $entityManager->remove($doctor);
            $entityManager->flush();

            return $this->json(['message' => 'Doctor deleted successfully']);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete doctor',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/specialization/{specialization}', name: 'app_doctor_by_specialization', methods: ['GET'])]
    public function findBySpecialization(string $specialization, EntityManagerInterface $entityManager): JsonResponse
    {
        $doctors = $entityManager->getRepository(Doctor::class)->findBySpecialization($specialization);
        
        return $this->json([
            'doctors' => array_map(function($doctor) {
                return [
                    'id' => $doctor->getId(),
                    'name' => $doctor->getName(),
                    
                    'email' => $doctor->getEmail(),
                    'specialization' => $doctor->getSpecialization(),
'displayName' => $doctor->getName(),
                ];
            }, $doctors)
        ]);
    }

    #[Route('/{id}/create-user', name: 'app_doctor_create_user', methods: ['POST'])]
    public function createUserAccount(Doctor $doctor, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            if ($doctor->getUser()) {
                return $this->json([
                    'error' => 'User account already exists',
                    'message' => 'This doctor already has a user account'
                ], Response::HTTP_CONFLICT);
            }

            $data = json_decode($request->getContent(), true);
            
            // Create username from doctor's name if not provided
            $username = $data['username'] ?? strtolower(str_replace(' ', '.', $doctor->getName()));
            $password = $data['password'] ?? 'temp123456'; // Default password

            // Check if username already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUser) {
                $username = $username . '.' . $doctor->getId(); // Make it unique
            }

            // Create user account
            $user = new User();
            $user->setName($doctor->getName());
            $user->setEmail($doctor->getEmail());
            $user->setUsername($username);
            $user->setRoles(['ROLE_USER', 'ROLE_DOCTOR']);
            $user->setIsActive(true);
            $user->setAllowedPages(['dashboard', 'consultations', 'patients', 'queue']);

            // Hash password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Link doctor to user
            $doctor->setUser($user);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json([
                'message' => 'User account created successfully',
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'temporaryPassword' => $password // Return for admin to share
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create user account',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/unlink-user', name: 'app_doctor_unlink_user', methods: ['DELETE'])]
    public function unlinkUserAccount(Doctor $doctor, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $user = $doctor->getUser();
            if (!$user) {
                return $this->json([
                    'error' => 'No user account linked',
                    'message' => 'This doctor does not have a user account'
                ], Response::HTTP_NOT_FOUND);
            }

            // Unlink doctor from user
            $doctor->setUser(null);
            
            // Optionally delete the user account or keep it
            // For safety, we'll just unlink but keep the user account
            $entityManager->flush();

            return $this->json(['message' => 'User account unlinked successfully']);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to unlink user account',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
