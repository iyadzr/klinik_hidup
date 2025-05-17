<?php

namespace App\Controller;

use App\Entity\Doctor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/doctors')]
class DoctorController extends AbstractController
{
    #[Route('', name: 'app_doctor_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $doctors = $entityManager->getRepository(Doctor::class)->findAll();
        $result = array_map(function($doctor) {
            return [
                'id' => $doctor->getId(),
                'firstName' => $doctor->getFirstName(),
                'lastName' => $doctor->getLastName(),
                'email' => $doctor->getEmail(),
                'phone' => $doctor->getPhone(),
                'specialization' => $doctor->getSpecialization(),
                'displayName' => $doctor->getName(),
            ];
        }, $doctors);
        return $this->json($result);
    }

    #[Route('/count', name: 'app_doctor_count', methods: ['GET'])]
    public function count(EntityManagerInterface $entityManager): JsonResponse
    {
        $count = $entityManager->getRepository(Doctor::class)->count([]);
        
        return $this->json(['count' => $count]);
    }

    #[Route('', name: 'app_doctor_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $doctor = new Doctor();
        $doctor->setFirstName($data['firstName']);
        $doctor->setLastName($data['lastName']);
        $doctor->setEmail($data['email']);
        $doctor->setPhone($data['phone']);
        $doctor->setSpecialization($data['specialization']);
        if (isset($data['licenseNumber'])) {
            $doctor->setLicenseNumber($data['licenseNumber']);
        }
        if (isset($data['workingHours'])) {
            $doctor->setWorkingHours($data['workingHours']);
        }

        $entityManager->persist($doctor);
        $entityManager->flush();

        return $this->json([
            'message' => 'Doctor created successfully',
            'doctor' => [
                'id' => $doctor->getId(),
                'firstName' => $doctor->getFirstName(),
                'lastName' => $doctor->getLastName(),
                'email' => $doctor->getEmail(),
                'specialization' => $doctor->getSpecialization(),
                'displayName' => $doctor->getName(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_doctor_show', methods: ['GET'])]
    public function show(Doctor $doctor): JsonResponse
    {
        return $this->json([
            'doctor' => [
                'id' => $doctor->getId(),
                'firstName' => $doctor->getFirstName(),
                'lastName' => $doctor->getLastName(),
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

        if (isset($data['firstName'])) {
            $doctor->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $doctor->setLastName($data['lastName']);
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
                'firstName' => $doctor->getFirstName(),
                'lastName' => $doctor->getLastName(),
                'email' => $doctor->getEmail(),
                'specialization' => $doctor->getSpecialization(),
'displayName' => $doctor->getName(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_doctor_delete', methods: ['DELETE'])]
    public function delete(Doctor $doctor, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($doctor);
        $entityManager->flush();

        return $this->json(['message' => 'Doctor deleted successfully']);
    }

    #[Route('/specialization/{specialization}', name: 'app_doctor_by_specialization', methods: ['GET'])]
    public function findBySpecialization(string $specialization, EntityManagerInterface $entityManager): JsonResponse
    {
        $doctors = $entityManager->getRepository(Doctor::class)->findBySpecialization($specialization);
        
        return $this->json([
            'doctors' => array_map(function($doctor) {
                return [
                    'id' => $doctor->getId(),
                    'firstName' => $doctor->getFirstName(),
                    'lastName' => $doctor->getLastName(),
                    'email' => $doctor->getEmail(),
                    'specialization' => $doctor->getSpecialization(),
'displayName' => $doctor->getName(),
                ];
            }, $doctors)
        ]);
    }
}
