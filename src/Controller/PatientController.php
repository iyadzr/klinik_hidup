<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Doctor;
use App\Entity\Queue;
use App\Entity\Consultation;
use App\Repository\PatientRepository;
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
    public function index(Request $request, PatientRepository $patientRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $patients = $patientRepository->findPaginated($page, $limit);
        $total = $patientRepository->count([]);

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

        return $this->json([
            'data' => $result,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
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
    public function search(Request $request, PatientRepository $patientRepository): JsonResponse
    {
        try {
            $query = trim($request->query->get('query', ''));
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 10);

            if (empty($query)) {
                // Return paginated results for empty search, same as index
                return $this->index($request, $patientRepository);
            }
            
            if (strlen($query) < 2) {
                return $this->json([
                    'data' => [],
                    'total' => 0,
                    'page' => 1,
                    'limit' => $limit,
                    'error' => 'Query too short. Minimum 2 characters required.'
                ], 400);
            }

            $results = $patientRepository->findBySearchTermPaginated($query, $page, $limit);
            $total = $patientRepository->countBySearchTerm($query);
            
            $formattedResults = array_map(function($patient) {
                return [
                    'id' => $patient->getId(),
                    'name' => $patient->getName() ?? '',
                    'nric' => $patient->getNric() ?? '',
                    'email' => $patient->getEmail() ?? '',
                    'phone' => $patient->getPhone() ?? '',
                    'dateOfBirth' => $patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : null,
                    'gender' => $patient->getGender() ?? '',
                    'address' => $patient->getAddress() ?? '',
                    'company' => $patient->getCompany() ?? '',
                    'preInformedIllness' => $patient->getPreInformedIllness() ?? '',
                    'medicalHistory' => $patient->getMedicalHistory() ?? '',
                    'registrationNumber' => null
                ];
            }, $results);
            
            return $this->json([
                'data' => $formattedResults,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
            
        } catch (\Exception $e) {
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

    #[Route('/register', name: 'app_patient_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON data'], 400);
            }

            $this->logger->info('Patient registration request received', ['data' => $data]);

            // Handle multiple patients (group consultation)
            if (isset($data['patients']) && is_array($data['patients'])) {
                return $this->registerMultiplePatients($data, $entityManager);
            }

            // Handle single patient registration
            if (!isset($data['patient']) || !isset($data['doctorId'])) {
                return $this->json(['error' => 'Patient data and Doctor ID are required'], 400);
            }

            $patientData = $data['patient'];
            $doctorId = $data['doctorId'];

            // Validate required fields
            $requiredFields = ['name', 'nric', 'phone', 'dateOfBirth'];
            foreach ($requiredFields as $field) {
                if (empty($patientData[$field])) {
                    return $this->json(['error' => "Field '$field' is required"], 400);
                }
            }

            // Check if doctor exists
            $doctor = $entityManager->getRepository(Doctor::class)->find($doctorId);
            if (!$doctor) {
                return $this->json(['error' => 'Doctor not found'], 404);
            }

            // Check for duplicate NRIC
            $existingPatient = $entityManager->getRepository(Patient::class)
                ->findOneBy(['nric' => $patientData['nric']]);
            
            if ($existingPatient) {
                // Patient exists, just add to queue
                $queue = $this->createQueueEntry($existingPatient, $doctor, $patientData, $entityManager);
                
                return $this->json([
                    'message' => 'Existing patient added to queue successfully',
                    'patientId' => $existingPatient->getId(),
                    'queueId' => $queue->getId(),
                    'queueNumber' => $queue->getQueueNumber(),
                    'registrationNumber' => $queue->getRegistrationNumber()
                ], 201);
            }

            // Create new patient
            $patient = new Patient();
            $patient->setName($patientData['name']);
            $patient->setNric($patientData['nric']);
            $patient->setEmail($patientData['email'] ?? '');
            $patient->setPhone($patientData['phone']);
            
            try {
                if (isset($patientData['dateOfBirth'])) {
                    $patient->setDateOfBirth(new \DateTime($patientData['dateOfBirth']));
                }
            } catch (\Exception $e) {
                return $this->json(['error' => 'Invalid date format'], 400);
            }
            
            if (isset($patientData['medicalHistory'])) {
                $patient->setMedicalHistory($patientData['medicalHistory']);
            }
            if (isset($patientData['gender'])) {
                $patient->setGender($patientData['gender']);
            }
            if (isset($patientData['address'])) {
                $patient->setAddress($patientData['address']);
            }
            if (isset($patientData['company'])) {
                $patient->setCompany($patientData['company']);
            }
            if (isset($patientData['preInformedIllness'])) {
                $patient->setPreInformedIllness($patientData['preInformedIllness']);
            }

            $entityManager->persist($patient);
            $entityManager->flush();

            // Create queue entry
            $queue = $this->createQueueEntry($patient, $doctor, $patientData, $entityManager);

            $this->logger->info('Patient registered and queued successfully', [
                'patientId' => $patient->getId(),
                'queueId' => $queue->getId(),
                'queueNumber' => $queue->getQueueNumber()
            ]);

            return $this->json([
                'message' => 'Patient registered and queued successfully',
                'patientId' => $patient->getId(),
                'queueId' => $queue->getId(),
                'queueNumber' => $queue->getQueueNumber(),
                'registrationNumber' => $queue->getRegistrationNumber(),
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
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Error in patient registration', [
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

    private function createQueueEntry(Patient $patient, Doctor $doctor, array $patientData, EntityManagerInterface $entityManager): Queue
    {
        $queue = new Queue();
        $queue->setPatient($patient);
        $queue->setDoctor($doctor);
        $queue->setStatus('waiting');
        
        // Set queue date/time
        $queueDateTime = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
        $queue->setQueueDateTime($queueDateTime);
        $hour = (int)$queueDateTime->format('G'); // 0-23, e.g., 8, 9, 15
        
        // Find the latest queue number for this hour block today
        $qb = $entityManager->getRepository(Queue::class)->createQueryBuilder('q');
        $qb->select('q.queueNumber')
            ->where('q.queueDateTime >= :start')
            ->andWhere('q.queueDateTime < :end')
            ->setParameter('start', $queueDateTime->format('Y-m-d ') . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00')
            ->setParameter('end', $queueDateTime->format('Y-m-d ') . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':59:59')
            ->orderBy('q.queueNumber', 'DESC')
            ->setMaxResults(1);
        $lastQueue = $qb->getQuery()->getOneOrNullResult();
        
        $runningNumber = 1;
        if ($lastQueue && isset($lastQueue['queueNumber'])) {
            $lastNum = (int)substr($lastQueue['queueNumber'], -2);
            $runningNumber = $lastNum + 1;
        }
        $queueNumber = sprintf('%d%02d', $hour, $runningNumber); // e.g., 8001, 9002, 1501
        $queue->setQueueNumber($queueNumber);
        $queue->setRegistrationNumber((int)$queueNumber); // Convert string to integer
        
        $entityManager->persist($queue);
        $entityManager->flush();
        
        return $queue;
    }

    private function registerMultiplePatients(array $data, EntityManagerInterface $entityManager): JsonResponse
    {
        // Implementation for multiple patients (group consultation)
        // This would be similar to the single patient logic but in a loop
        // For now, return an error to indicate it's not implemented
        return $this->json(['error' => 'Multiple patient registration not yet implemented'], 501);
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

    #[Route('/doctor/{doctorId}', name: 'app_patients_by_doctor', methods: ['GET'])]
    public function getPatientsByDoctor(int $doctorId, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Get patients who have had consultations with this doctor or are in their queue
            $qb = $entityManager->createQueryBuilder();
            
            // Get patient IDs from consultations
            $consultationPatients = $qb
                ->select('DISTINCT p.id')
                ->from(Patient::class, 'p')
                ->join('p.consultations', 'c')
                ->where('c.doctor = :doctorId')
                ->setParameter('doctorId', $doctorId)
                ->getQuery()
                ->getScalarResult();
            
            // Get patient IDs from current queue
            $qb2 = $entityManager->createQueryBuilder();
            $queuePatients = $qb2
                ->select('DISTINCT p.id')
                ->from(Patient::class, 'p')
                ->join(Queue::class, 'q', 'WITH', 'q.patient = p.id')
                ->where('q.doctor = :doctorId')
                ->andWhere('q.queueDateTime >= :today')
                ->setParameter('doctorId', $doctorId)
                ->setParameter('today', new \DateTime('today', new \DateTimeZone('Asia/Kuala_Lumpur')))
                ->getQuery()
                ->getScalarResult();
            
            // Combine and get unique patient IDs
            $allPatientIds = array_unique(array_merge(
                array_column($consultationPatients, 'id'),
                array_column($queuePatients, 'id')
            ));
            
            if (empty($allPatientIds)) {
                return new JsonResponse([]);
            }
            
            // Get full patient data
            $patients = $entityManager->getRepository(Patient::class)
                ->createQueryBuilder('p')
                ->where('p.id IN (:patientIds)')
                ->setParameter('patientIds', $allPatientIds)
                ->orderBy('p.name', 'ASC')
                ->getQuery()
                ->getResult();

            $patientsData = [];
            foreach ($patients as $patient) {
                $patientsData[] = [
                    'id' => $patient->getId(),
                    'name' => $patient->getName(),
                    'nric' => $patient->getNric(),
                    'email' => $patient->getEmail(),
                    'phone' => $patient->getPhone(),
                    'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                    'gender' => $patient->getGender(),
                    'address' => $patient->getAddress(),
                    'medicalHistory' => $patient->getMedicalHistory(),
                    'company' => $patient->getCompany(),
                    'preInformedIllness' => $patient->getPreInformedIllness(),
                    'registeredBy' => $patient->getRegisteredBy() ? [
                        'id' => $patient->getRegisteredBy()->getId(),
                        'name' => $patient->getRegisteredBy()->getName()
                    ] : null
                ];
            }

            return new JsonResponse($patientsData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch patients: ' . $e->getMessage()], 500);
        }
    }
}
