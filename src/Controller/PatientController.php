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
            
            // Rate limiting check - minimum 2 characters to prevent too many queries
            if (strlen($query) < 2) {
                return $this->json(['error' => 'Query too short. Minimum 2 characters required.'], 400);
            }
            
            $searchPattern = '%' . $query . '%';
            
            // Optimized database query with better performance
            $qb = $entityManager->createQueryBuilder();
            $qb->select('p.id, p.name, p.nric, p.email, p.phone, p.dateOfBirth, p.gender, p.address, p.company, p.preInformedIllness, p.medicalHistory')
               ->from(Patient::class, 'p')
               ->where('p.name LIKE :query')
               ->orWhere('p.phone LIKE :query')
               ->orWhere('p.nric LIKE :query')
               ->setParameter('query', $searchPattern)
               ->setMaxResults(20) // Reduced limit for better performance
               ->orderBy('p.name', 'ASC'); // Consistent ordering
            
            // Use array hydration for better performance (no object instantiation)
            $results = $qb->getQuery()->getArrayResult();
            
            // Format results with proper null handling
            $formattedResults = array_map(function($patient) {
                return [
                    'id' => $patient['id'],
                    'name' => $patient['name'] ?? '',
                    'nric' => $patient['nric'] ?? '',
                    'email' => $patient['email'] ?? '',
                    'phone' => $patient['phone'] ?? '',
                    'dateOfBirth' => $patient['dateOfBirth'] ? $patient['dateOfBirth']->format('Y-m-d') : null,
                    'gender' => $patient['gender'] ?? '',
                    'address' => $patient['address'] ?? '',
                    'company' => $patient['company'] ?? '',
                    'preInformedIllness' => $patient['preInformedIllness'] ?? '',
                    'medicalHistory' => $patient['medicalHistory'] ?? '',
                    'registrationNumber' => null
                ];
            }, $results);
            
            // Set cache headers for client-side performance
            $response = $this->json($formattedResults);
            $response->headers->set('Cache-Control', 'private, max-age=300'); // 5 minutes
            $response->headers->set('X-Search-Results-Count', count($formattedResults));
            
            return $response;
            
        } catch (\Exception $e) {
            // Enhanced error logging for production debugging
            $this->logger->error('Patient search error', [
                'query' => $request->query->get('query', ''),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'error' => 'Search failed',
                'message' => 'Please try again later'
            ], 500);
        }
    }

    #[Route('', name: 'app_patient_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], 400);
            }

            // Validate required fields
            $requiredFields = ['name', 'nric', 'phone', 'dateOfBirth'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->json(['error' => "Field '$field' is required"], 400);
                }
            }

            // Check for duplicate NRIC
            $existingPatient = $entityManager->getRepository(Patient::class)
                ->findOneBy(['nric' => $data['nric']]);
            
            if ($existingPatient) {
                return $this->json(['error' => 'A patient with this NRIC already exists'], 409);
            }

            $patient = new Patient();
            $patient->setName($data['name']);
            $patient->setNric($data['nric']);
            $patient->setEmail($data['email'] ?? '');
            $patient->setPhone($data['phone']);
            
            try {
                if (isset($data['dateOfBirth'])) {
                    $patient->setDateOfBirth(new \DateTime($data['dateOfBirth']));
                }
            } catch (\Exception $e) {
                return $this->json(['error' => 'Invalid date format'], 400);
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
        } catch (\Exception $e) {
            $this->logger->error('Error creating patient', [
                'error' => $e->getMessage(),
                'data' => $data ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'error' => 'Failed to register patient. Please try again.',
                'details' => $e->getMessage()
            ], 500);
        }
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
