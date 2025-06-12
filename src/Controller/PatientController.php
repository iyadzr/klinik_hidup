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
    #[Route('/registration/{registrationNumber}', name: 'app_patient_by_registration', methods: ['GET'])]
    public function getByRegistrationNumber(int $registrationNumber, EntityManagerInterface $entityManager): JsonResponse
    {
        $queue = $entityManager->getRepository(\App\Entity\Queue::class)->findOneBy(['registrationNumber' => $registrationNumber]);
        if (!$queue) {
            return $this->json(['error' => 'Registration number not found'], 404);
        }
        $patient = $queue->getPatient();
        if (!$patient) {
            return $this->json(['error' => 'Patient not found'], 404);
        }
        return $this->json([
            'id' => $patient->getId(),
            'name' => $patient->getName(),
            'nric' => $patient->getNric(),  
            'email' => $patient->getEmail(),
            'phone' => $patient->getPhone(),
            'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
            'medicalHistory' => $patient->getMedicalHistory(),
            'company' => method_exists($patient, 'getCompany') ? $patient->getCompany() : null,
            'preInformedIllness' => method_exists($patient, 'getPreInformedIllness') ? $patient->getPreInformedIllness() : null,
            'displayName' => $patient->getName(),
        ]);
    }

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
                'name' => $patient->getName(),
                'nric' => $patient->getNric(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'dateOfBirth' => $patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : null,
                'gender' => $patient->getGender(),
                'address' => $patient->getAddress(),
                'company' => $patient->getCompany(),
                'preInformedIllness' => $patient->getPreInformedIllness(),
                'medicalHistory' => $patient->getMedicalHistory(),
                'displayName' => $patient->getName(),
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
            'count' => $count
        ]);
        
        return $this->json(['count' => $count]);
    }

    #[Route('/search', name: 'app_patient_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $query = trim($request->query->get('query', ''));
            
            if (empty($query)) {
                return $this->json([]);
            }
            
            $searchPattern = '%' . $query . '%';
            
            // Optimized database query with proper indexing
            $qb = $entityManager->createQueryBuilder();
            $qb->select('p')
               ->from(Patient::class, 'p')
               ->where('p.name LIKE :query')
               ->orWhere('p.phone LIKE :query')
               ->orWhere('p.nric LIKE :query')
               ->setParameter('query', $searchPattern)
               ->setMaxResults(50); // Limit results for performance
            
            $patients = $qb->getQuery()->getResult();
            
            // Format results
            $result = array_map(function($patient) {
                return [
                    'id' => $patient->getId(),
                    'name' => $patient->getName(),
                    'nric' => $patient->getNric(),
                    'email' => $patient->getEmail(),
                    'phone' => $patient->getPhone(),
                    'dateOfBirth' => $patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : null,
                    'gender' => $patient->getGender(),
                    'address' => $patient->getAddress(),
                    'company' => $patient->getCompany(),
                    'preInformedIllness' => $patient->getPreInformedIllness(),
                    'medicalHistory' => $patient->getMedicalHistory(),
                    'registrationNumber' => null
                ];
            }, $patients);
            
            return $this->json($result);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Search failed',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    #[Route('', name: 'app_patient_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $patient = new Patient();
        $patient->setName($data['name']);
        $patient->setNric($data['nric']);
        $patient->setEmail($data['email'] ?? '');
        $patient->setPhone($data['phone']);
        if (isset($data['dateOfBirth'])) {
            $patient->setDateOfBirth(new \DateTime($data['dateOfBirth']));
        }
        if (isset($data['medicalHistory'])) {
            $patient->setMedicalHistory($data['medicalHistory']);
        }
        if (isset($data['gender'])) {
            $patient->setGender($data['gender']);
        }
        if (isset($data['address'])) {
            $patient->setAddress($data['address']);
        }
        if (isset($data['company'])) {
            $patient->setCompany($data['company']);
        }
        if (isset($data['preInformedIllness'])) {
            $patient->setPreInformedIllness($data['preInformedIllness']);
        }

        $entityManager->persist($patient);
        $entityManager->flush();

        $this->logger->info('Patient created', [
            'id' => $patient->getId(),
            'email' => $patient->getEmail(),
        ]);

        return $this->json([
            'message' => 'Patient created successfully',
            'id' => $patient->getId(),
            'registrationNumber' => null, // This will be set when added to queue
            'patient' => [
                'id' => $patient->getId(),
                'name' => $patient->getName(),
                'nric' => $patient->getNric(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                'gender' => $patient->getGender(),
                'address' => $patient->getAddress(),
                'company' => $patient->getCompany(),
                'preInformedIllness' => $patient->getPreInformedIllness(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_patient_show', methods: ['GET'])]
    public function show(Patient $patient): JsonResponse
    {
        return $this->json([
            'id' => $patient->getId(),
            'name' => $patient->getName(),
            'nric' => $patient->getNric(),
            'email' => $patient->getEmail(),
            'phone' => $patient->getPhone(),
            'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
            'gender' => $patient->getGender(),
            'address' => $patient->getAddress(),
            'company' => $patient->getCompany(),
            'preInformedIllness' => $patient->getPreInformedIllness(),
            'medicalHistory' => $patient->getMedicalHistory(),
            'displayName' => $patient->getName(),
        ]);
    }

    #[Route('/{id}', name: 'app_patient_update', methods: ['PUT'])]
    public function update(Request $request, Patient $patient, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $patient->setName($data['name']);
        }
        if (isset($data['nric'])) {
            $patient->setNric($data['nric']);
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
        if (isset($data['gender'])) {
            $patient->setGender($data['gender']);
        }
        if (isset($data['address'])) {
            $patient->setAddress($data['address']);
        }
        if (isset($data['company'])) {
            $patient->setCompany($data['company']);
        }
        if (isset($data['preInformedIllness'])) {
            $patient->setPreInformedIllness($data['preInformedIllness']);
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
                'name' => $patient->getName(),
                'nric' => $patient->getNric(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                'gender' => $patient->getGender(),
                'address' => $patient->getAddress(),
                'company' => $patient->getCompany(),
                'preInformedIllness' => $patient->getPreInformedIllness(),
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
