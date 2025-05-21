<?php

namespace App\Controller;

use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/patients')]
class PatientController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('', name: 'app_patient_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $patients = $entityManager->getRepository(Patient::class)->findAll();
        $result = array_map(function($patient) {
            return [
                'id' => $patient->getId(),
                'firstName' => $patient->getFirstName(),
                'lastName' => $patient->getLastName(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                'medicalHistory' => $patient->getMedicalHistory(),
                'displayName' => method_exists($patient, 'getDisplayName') ? $patient->getDisplayName() : (method_exists($patient, 'getName') ? $patient->getName() : trim($patient->getFirstName() . ' ' . $patient->getLastName())),
            ];
        }, $patients);
        return $this->json($result);
    }

    #[Route('/count', name: 'app_patient_count', methods: ['GET'])]
    public function count(EntityManagerInterface $entityManager): JsonResponse
    {
        $repository = $entityManager->getRepository(Patient::class);
        $count = $repository->count([]);
        
        $this->logger->info('Patient count requested', [
            'count' => $count,
            'raw_patients' => $repository->findAll()
        ]);
        
        return $this->json(['count' => $count]);
    }

    #[Route('', name: 'app_patient_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $patient = new Patient();
        $patient->setFirstName($data['firstName']);
        $patient->setLastName($data['lastName']);
        $patient->setEmail($data['email']);
        $patient->setPhone($data['phone']);
        if (isset($data['dateOfBirth'])) {
            $patient->setDateOfBirth(new \DateTime($data['dateOfBirth']));
        }
        if (isset($data['medicalHistory'])) {
            $patient->setMedicalHistory($data['medicalHistory']);
        }

        $entityManager->persist($patient);
        $entityManager->flush();

        $this->logger->info('Patient created', [
            'id' => $patient->getId(),
            'email' => $patient->getEmail()
        ]);

        return $this->json([
            'message' => 'Patient created successfully',
            'patient' => [
                'id' => $patient->getId(),
                'firstName' => $patient->getFirstName(),
                'lastName' => $patient->getLastName(),
                'email' => $patient->getEmail(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_patient_show', methods: ['GET'])]
    public function show(Patient $patient): JsonResponse
    {
        return $this->json([
            'id' => $patient->getId(),
            'firstName' => $patient->getFirstName(),
            'lastName' => $patient->getLastName(),
            'email' => $patient->getEmail(),
            'phone' => $patient->getPhone(),
            'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
            'medicalHistory' => $patient->getMedicalHistory(),
            'company' => $patient->getCompany(),
            'preInformedIllness' => $patient->getPreInformedIllness(),
            'displayName' => $patient->getName(),
        ]);
    }

    #[Route('/{id}', name: 'app_patient_update', methods: ['PUT'])]
    public function update(Request $request, Patient $patient, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['firstName'])) {
            $patient->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $patient->setLastName($data['lastName']);
        }
        if (isset($data['email'])) {
            $patient->setEmail($data['email']);
        }
        if (isset($data['phone'])) {
            $patient->setPhone($data['phone']);
        }
        if (isset($data['dateOfBirth'])) {
            $patient->setDateOfBirth(new \DateTime($data['dateOfBirth']));
        }
        if (isset($data['medicalHistory'])) {
            $patient->setMedicalHistory($data['medicalHistory']);
        }

        $entityManager->flush();

        $this->logger->info('Patient updated', [
            'id' => $patient->getId(),
            'email' => $patient->getEmail()
        ]);

        return $this->json([
            'message' => 'Patient updated successfully',
            'patient' => [
                'id' => $patient->getId(),
                'firstName' => $patient->getFirstName(),
                'lastName' => $patient->getLastName(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                'medicalHistory' => $patient->getMedicalHistory(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_patient_delete', methods: ['DELETE'])]
    public function delete(Patient $patient, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($patient);
        $entityManager->flush();

        $this->logger->info('Patient deleted', [
            'id' => $patient->getId()
        ]);

        return $this->json(['message' => 'Patient deleted successfully']);
    }
}
