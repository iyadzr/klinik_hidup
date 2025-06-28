<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\Patient;
use App\Entity\Doctor;
use App\Entity\Queue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/consultations')]
class ConsultationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ?CacheInterface $cache;
    
    // Rate limiting storage
    private static array $requestCounts = [];
    private static int $maxRequestsPerMinute = 30;

    public function __construct(
        EntityManagerInterface $entityManager, 
        LoggerInterface $logger,
        ?CacheInterface $cache = null
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->cache = $cache;
    }
    
    private function checkRateLimit(Request $request): bool
    {
        $clientIp = $request->getClientIp() ?? 'unknown';
        $currentMinute = (int)(time() / 60);
        
        // Clean old entries
        self::$requestCounts = array_filter(
            self::$requestCounts, 
            fn($data) => $data['minute'] >= $currentMinute - 1
        );
        
        // Count requests for this IP in current minute
        $key = $clientIp . '_' . $currentMinute;
        if (!isset(self::$requestCounts[$key])) {
            self::$requestCounts[$key] = ['minute' => $currentMinute, 'count' => 0];
        }
        
        self::$requestCounts[$key]['count']++;
        
        return self::$requestCounts[$key]['count'] <= self::$maxRequestsPerMinute;
    }
    
    private function broadcastQueueUpdate($queue): void
    {
        // Store the update in a temporary file for SSE to pick up
        $updateData = [
            'type' => 'queue_status_update',
            'timestamp' => time(),
            'data' => [
                'id' => $queue->getId(),
                'queueNumber' => $queue->getQueueNumber(),
                'registrationNumber' => $queue->getRegistrationNumber(),
                'status' => $queue->getStatus(),
                'patient' => [
                    'id' => $queue->getPatient()->getId(),
                    'name' => $queue->getPatient()->getName(),
                ],
                'doctor' => [
                    'id' => $queue->getDoctor()->getId(),
                    'name' => $queue->getDoctor()->getName(),
                ],
                'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s')
            ]
        ];
        
        // Write to temporary file that SSE endpoint will read
        $tempDir = sys_get_temp_dir();
        $updateFile = $tempDir . '/queue_updates.json';
        
        // Read existing updates
        $updates = [];
        if (file_exists($updateFile)) {
            $content = file_get_contents($updateFile);
            $updates = json_decode($content, true) ?: [];
        }
        
        // Add new update
        $updates[] = $updateData;
        
        // Keep only last 10 updates to prevent file from growing too large
        $updates = array_slice($updates, -10);
        
        // Write back to file
        file_put_contents($updateFile, json_encode($updates));
    }

    #[Route('', name: 'app_consultation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $data = json_decode($request->getContent(), true);
            $this->logger->info('Received consultation data', ['data' => $data]);
            
            // Get Patient
            $patient = $this->entityManager->getRepository(Patient::class)->find($data['patientId']);
            if (!$patient) {
                return new JsonResponse(['error' => 'Patient not found'], 404);
            }
            
            // Get Doctor if doctor ID is provided
            $doctor = null;
            if (!empty($data['doctorId'])) {
                $doctor = $this->entityManager->getRepository(Doctor::class)->find($data['doctorId']);
                if (!$doctor) {
                    return new JsonResponse(['error' => 'Doctor not found'], 404);
                }
            }
            
            // Create new consultation
            $consultation = new Consultation();
            $consultation->setPatient($patient);
            
            // Set doctor if available
            if ($doctor) {
                $consultation->setDoctor($doctor);
            }
            
            // Set consultation date to now in MYT timezone
            $myt = new \DateTimeZone('Asia/Kuala_Lumpur');
            $now = new \DateTime('now', $myt);
            $consultation->setConsultationDate($now);
            
            // Ensure createdAt is also set properly (constructor should handle this, but let's be explicit)
            if (!$consultation->getCreatedAt()) {
                $consultation->setCreatedAt($now);
            }
            
            // Set all fields from the request data
            if (isset($data['symptoms'])) $consultation->setSymptoms($data['symptoms']);
            if (isset($data['diagnosis'])) $consultation->setDiagnosis($data['diagnosis']);
            if (isset($data['treatment'])) $consultation->setTreatment($data['treatment']);
            if (isset($data['medications'])) $consultation->setMedications($data['medications']);
            if (isset($data['notes'])) $consultation->setNotes($data['notes']);
            
            // Handle payment information
            if (isset($data['totalAmount'])) $consultation->setTotalAmount($data['totalAmount']);
            
            // Handle medical certificate information
            if (isset($data['hasMedicalCertificate']) && $data['hasMedicalCertificate']) {
                $consultation->setHasMedicalCertificate(true);
                if (isset($data['mcStartDate'])) $consultation->setMcStartDate(new \DateTime($data['mcStartDate']));
                if (isset($data['mcEndDate'])) $consultation->setMcEndDate(new \DateTime($data['mcEndDate']));
                if (isset($data['mcNumber'])) $consultation->setMcNumber($data['mcNumber']);
                if (isset($data['mcRunningNumber'])) $consultation->setMcRunningNumber($data['mcRunningNumber']);
            }
            
            $this->entityManager->persist($consultation);
            $this->entityManager->flush();
            
            // Handle prescribed medications if provided
            if (isset($data['prescribedMedications']) && is_array($data['prescribedMedications'])) {
                foreach ($data['prescribedMedications'] as $medData) {
                    if (!empty($medData['name']) && !empty($medData['quantity'])) {
                        // Find or create medication
                        $medication = null;
                        if (!empty($medData['medicationId'])) {
                            $medication = $this->entityManager->getRepository(\App\Entity\Medication::class)->find($medData['medicationId']);
                        }
                        
                        // If medication not found by ID, try to find by name
                        if (!$medication && !empty($medData['name'])) {
                            $medication = $this->entityManager->getRepository(\App\Entity\Medication::class)->findOneBy(['name' => $medData['name']]);
                        }
                        
                        // Create new medication if it doesn't exist
                        if (!$medication) {
                            $medication = new \App\Entity\Medication();
                            $medication->setName($medData['name']);
                            $medication->setUnitType($medData['unitType'] ?? 'pieces');
                            $medication->setUnitDescription($medData['unitDescription'] ?? null);
                            $medication->setCategory($medData['category'] ?? null);
                            $this->entityManager->persist($medication);
                            $this->entityManager->flush(); // Flush to get the ID
                        }
                        
                        // Create prescribed medication record
                        $prescribedMed = new \App\Entity\PrescribedMedication();
                        $prescribedMed->setConsultation($consultation);
                        $prescribedMed->setMedication($medication);
                        
                        if (isset($medData['medicationId'])) {
                            $medication = $this->entityManager->getRepository(\App\Entity\Medication::class)->find($medData['medicationId']);
                            if ($medication) {
                                $prescribedMed->setMedication($medication);
                            }
                        }
                        
                        if (isset($medData['quantity'])) {
                            $prescribedMed->setQuantity((int)$medData['quantity']);
                            $prescribedMed->setInstructions($medData['instructions'] ?? null);
                            
                            // Handle actual price set by doctor
                            if (isset($medData['actualPrice']) && $medData['actualPrice'] > 0) {
                                $prescribedMed->setActualPrice((string)$medData['actualPrice']);
                            }
                            
                            $this->entityManager->persist($prescribedMed);
                        }
                    }
                }
                $this->entityManager->flush();
            }
            
            // Update queue status to 'completed_consultation' for this patient/doctor combination
            $queueRepository = $this->entityManager->getRepository(\App\Entity\Queue::class);
            $queue = $queueRepository->findOneBy([
                'patient' => $patient,
                'doctor' => $doctor,
                'status' => 'in_consultation'
            ]);
            
            if ($queue) {
                $queue->setStatus('completed_consultation');
                
                // Also update the consultation status
                $consultation->setStatus('completed_consultation');
                
                $this->entityManager->flush();
                
                // Trigger real-time update
                $this->broadcastQueueUpdate($queue);
            }
            
            // Clear relevant caches
            if ($this->cache) {
                $this->cache->delete('consultations_list_' . date('Y-m-d'));
                $this->cache->delete('consultations_ongoing');
            }
            
            return new JsonResponse([
                'id' => $consultation->getId(),
                'message' => 'Consultation created successfully',
                'queueUpdated' => $queue ? true : false
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Error creating consultation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => 'Error creating consultation: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/patient/{id}', name: 'app_consultation_history', methods: ['GET'])]
    public function getPatientHistory(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $cacheKey = "patient_history_$id";
            
            if ($this->cache) {
                $history = $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
                    $item->expiresAfter(300); // 5 minutes cache
                    return $this->buildPatientHistory($id);
                });
            } else {
                $history = $this->buildPatientHistory($id);
            }
            
            return new JsonResponse($history);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching patient history: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch patient history'], 500);
        }
    }
    
    private function buildPatientHistory(int $patientId): array
    {
        $consultations = $this->entityManager->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->where('c.patient = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('c.consultationDate', 'DESC')
            ->setMaxResults(50) // Limit to last 50 consultations
            ->getQuery()
            ->getResult();

        $history = [];
        foreach ($consultations as $consultation) {
            // Parse medications if it's stored as JSON string
            $medications = $consultation->getMedications();
            $parsedMedications = [];
            if ($medications) {
                $decoded = json_decode($medications, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $parsedMedications = $decoded;
                }
            }

            $history[] = [
                'id' => $consultation->getId(),
                'consultationDate' => $consultation->getConsultationDate()->format('Y-m-d\TH:i'),
                'diagnosis' => $consultation->getDiagnosis(),
                'medications' => $parsedMedications, // Parsed medications array
                'notes' => $consultation->getNotes(),
                'symptoms' => $consultation->getSymptoms(),
                'treatment' => $consultation->getTreatment(),
                'followUpPlan' => $consultation->getFollowUpPlan(),
                'consultationFee' => $consultation->getConsultationFee(),
                'medicinesFee' => $consultation->getMedicinesFee(),
                'totalAmount' => $consultation->getTotalAmount(),
                'isPaid' => $consultation->getIsPaid(),
                'status' => $consultation->getIsPaid() ? 'Paid' : 'Unpaid',
                'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null,
                'hasMedicalCertificate' => $consultation->getHasMedicalCertificate() ?? false,
                'mcStartDate' => $consultation->getMcStartDate() ? $consultation->getMcStartDate()->format('Y-m-d') : null,
                'mcEndDate' => $consultation->getMcEndDate() ? $consultation->getMcEndDate()->format('Y-m-d') : null,
                'mcNotes' => method_exists($consultation, 'getMcNotes') ? $consultation->getMcNotes() : null,
                'mcNumber' => method_exists($consultation, 'getMcNumber') ? $consultation->getMcNumber() : null,
                'queueNumber' => method_exists($consultation, 'getQueueNumber') ? $consultation->getQueueNumber() : null,
                'doctor' => [
                    'id' => $consultation->getDoctor()->getId(),
                    'name' => $consultation->getDoctor()->getName()
                ],
                'patient' => [
                    'id' => $consultation->getPatient()->getId(),
                    'name' => $consultation->getPatient()->getName()
                ],
                'receiptNumber' => method_exists($consultation, 'getReceiptNumber') ? $consultation->getReceiptNumber() : null
            ];
        }
        
        return $history;
    }

    #[Route('', name: 'app_consultations_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $date = $request->query->get('date');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(10, (int) $request->query->get('limit', 20))); // Max 50, min 10
            
            $cacheKey = 'consultations_list_' . ($date ?: 'all') . '_page_' . $page . '_limit_' . $limit;
            
            if ($this->cache) {
                $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($date, $page, $limit) {
                    $item->expiresAfter(120); // 2 minutes cache
                    return $this->buildConsultationsList($date, $page, $limit);
                });
            } else {
                $data = $this->buildConsultationsList($date, $page, $limit);
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching consultations list: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch consultations'], 500);
        }
    }
    
    private function buildConsultationsList(?string $date, int $page, int $limit): array
    {
        $qb = $this->entityManager->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->select('c', 'p', 'd') // Select related entities to avoid N+1 queries
            ->leftJoin('c.patient', 'p')
            ->leftJoin('c.doctor', 'd')
            ->orderBy('c.createdAt', 'DESC');
        
        if ($date) {
            // Filter by specific date - use both createdAt and consultationDate for broader matching
            $start = new \DateTime($date . ' 00:00:00', new \DateTimeZone('Asia/Kuala_Lumpur'));
            $end = new \DateTime($date . ' 23:59:59', new \DateTimeZone('Asia/Kuala_Lumpur'));
            
            $qb->where('(c.createdAt BETWEEN :start AND :end) OR (c.consultationDate BETWEEN :start AND :end)')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        } else {
            // Return consultations from the last 30 days if no date filter (performance optimization)
            $thirtyDaysAgo = new \DateTime('-30 days', new \DateTimeZone('Asia/Kuala_Lumpur'));
            $qb->where('c.createdAt >= :thirtyDaysAgo')
               ->setParameter('thirtyDaysAgo', $thirtyDaysAgo);
        }
        
        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        
        $consultations = $qb->getQuery()->getResult();
        $data = [];

        foreach ($consultations as $consultation) {
            try {
                $patient = $consultation->getPatient();
                $doctor = $consultation->getDoctor();
                
                if (!$patient || !$doctor) {
                    continue; // Skip incomplete records
                }
                
                // Get prescribed medications efficiently
                $prescribedMeds = $this->entityManager->getRepository(\App\Entity\PrescribedMedication::class)
                    ->findBy(['consultation' => $consultation]);
                
                $medicationsArray = [];
                foreach ($prescribedMeds as $med) {
                    $medicationsArray[] = [
                        'name' => $med->getName(),
                        'quantity' => $med->getQuantity(),
                        'instructions' => $med->getInstructions(),
                        'actualPrice' => $med->getActualPrice()
                    ];
                }
                
                // Determine status with a fallback for older records
                $status = $consultation->getStatus();
                if ($status === null) {
                    $status = $consultation->getIsPaid() ? 'completed' : 'pending';
                }

                $data[] = [
                    'id' => $consultation->getId(),
                    'patientId' => $patient->getId(),
                    'patientName' => $patient->getName(),
                    'doctorId' => $doctor->getId(),
                    'doctorName' => $doctor->getName(),
                    'createdAt' => $consultation->getCreatedAt()->format('Y-m-d H:i:s'),
                    'consultationDate' => $consultation->getConsultationDate() ? $consultation->getConsultationDate()->format('Y-m-d H:i:s') : $consultation->getCreatedAt()->format('Y-m-d H:i:s'),
                    'symptoms' => $consultation->getSymptoms(),
                    'diagnosis' => $consultation->getDiagnosis(),
                    'treatment' => $consultation->getTreatment(),
                    'notes' => $consultation->getNotes(),
                    'remarks' => $consultation->getNotes(),
                    'medications' => $consultation->getMedications(),
                    'prescribedMedications' => $medicationsArray,
                    'consultationFee' => $consultation->getConsultationFee(),
                    'medicinesFee' => $consultation->getMedicinesFee(),
                    'totalAmount' => $consultation->getTotalAmount(),
                    'isPaid' => $consultation->getIsPaid(),
                    'status' => $status,
                    'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null,
                    'receiptNumber' => $consultation->getReceiptNumber()
                ];
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Failed to build data for consultation ID: ' . ($consultation ? $consultation->getId() : 'unknown'),
                    [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                );
                continue;
            }
        }

        return $data;
    }

    #[Route('/ongoing', name: 'app_consultations_ongoing', methods: ['GET'])]
    public function ongoing(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $doctorId = $request->query->get('doctorId');
            $cacheKey = 'consultations_ongoing' . ($doctorId ? '_doctor_' . $doctorId : '');
            
            if ($this->cache) {
                $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($doctorId) {
                    $item->expiresAfter(30); // 30 seconds cache for real-time data
                    return $this->buildOngoingData($doctorId);
                });
            } else {
                $data = $this->buildOngoingData($doctorId);
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching ongoing consultations: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch ongoing consultations'], 500);
        }
    }
    
    private function buildOngoingData($doctorId = null): array
    {
        $today = new \DateTime('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
        
        // Set time to the beginning of the day for the start of the range
        $startOfDay = (clone $today)->setTime(0, 0, 0);
        
        // Set time to the end of the day for the end of the range
        $endOfDay = (clone $today)->setTime(23, 59, 59);

        $ongoingData = [];

        // Get ongoing consultations for the current day
        $consultationQb = $this->entityManager->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->select('c.id', 'c.consultationDate', 'p.name as patientName', 'd.name as doctorName', 'c.status', 'c.symptoms', 'p.id as patientId', 'd.id as doctorId')
            ->join('c.patient', 'p')
            ->join('c.doctor', 'd')
            ->where('c.status != :status_completed')
            ->andWhere('c.consultationDate BETWEEN :start AND :end')
            ->setParameter('status_completed', 'completed')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay);

        if ($doctorId) {
            $consultationQb->andWhere('d.id = :doctorId')
                          ->setParameter('doctorId', $doctorId);
        }

        $ongoingConsultations = $consultationQb->orderBy('c.consultationDate', 'ASC')
            ->getQuery()
            ->getResult();

        // Add ongoing consultations to the data
        foreach ($ongoingConsultations as $consultation) {
            $ongoingData[] = [
                'id' => $consultation['id'],
                'consultationDate' => $consultation['consultationDate'],
                'patientName' => $consultation['patientName'],
                'patientId' => $consultation['patientId'],
                'doctorName' => $consultation['doctorName'],
                'doctorId' => $consultation['doctorId'],
                'status' => $consultation['status'],
                'symptoms' => $consultation['symptoms'],
                'isQueueEntry' => false,
                'queueId' => null,
                'queueNumber' => null
            ];
        }

        // Get queue entries that are waiting for consultation
        $queueQb = $this->entityManager->getRepository(Queue::class)
            ->createQueryBuilder('q')
            ->select('q.id as queueId', 'q.queueNumber', 'q.queueDateTime', 'q.status', 'p.name as patientName', 'p.id as patientId', 'd.name as doctorName', 'd.id as doctorId', 'p.preInformedIllness as symptoms', 'q.metadata')
            ->join('q.patient', 'p')
            ->join('q.doctor', 'd')
            ->where('q.status IN (:statuses)')
            ->andWhere('q.queueDateTime BETWEEN :start AND :end')
            ->setParameter('statuses', ['waiting', 'in_consultation'])
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay);
            
        // Add doctor filter if specified
        if ($doctorId) {
            $queueQb->andWhere('d.id = :queueDoctorId')
                    ->setParameter('queueDoctorId', $doctorId);
        }
        
        $queueEntries = $queueQb->orderBy('q.queueNumber', 'ASC')
            ->getQuery()
            ->getResult();

        // Add queue entries to the data
        $groupedQueues = [];
        foreach ($queueEntries as $queue) {
            // Check if this is a group consultation
            $groupId = null;
            if (isset($queue['metadata']) && $queue['metadata']) {
                $meta = json_decode($queue['metadata'], true);
                if (isset($meta['groupId'])) {
                    $groupId = $meta['groupId'];
                }
            }
            if ($groupId) {
                // Group consultation: group by groupId
                if (!isset($groupedQueues[$groupId])) {
                    $groupedQueues[$groupId] = [
                        'id' => null,
                        'consultationDate' => $queue['queueDateTime'] instanceof \DateTimeInterface
                            ? $queue['queueDateTime']->format('Y-m-d H:i:s')
                            : $queue['queueDateTime'],
                        'doctorName' => $queue['doctorName'],
                        'doctorId' => $queue['doctorId'],
                        'status' => $queue['status'],
                        'isQueueEntry' => true,
                        'queueId' => $queue['queueId'],
                        'queueNumber' => $queue['queueNumber'],
                        'isGroupConsultation' => true,
                        'patients' => []
                    ];
                }
                $groupedQueues[$groupId]['patients'][] = [
                    'patientName' => $queue['patientName'],
                    'patientId' => $queue['patientId'],
                    'symptoms' => $queue['symptoms']
                ];
            } else {
                // Single patient queue
                $ongoingData[] = [
                    'id' => null,
                    'consultationDate' => $queue['queueDateTime'] instanceof \DateTimeInterface
                        ? $queue['queueDateTime']->format('Y-m-d H:i:s')
                        : $queue['queueDateTime'],
                    'patientName' => $queue['patientName'],
                    'patientId' => $queue['patientId'],
                    'doctorName' => $queue['doctorName'],
                    'doctorId' => $queue['doctorId'],
                    'status' => $queue['status'],
                    'symptoms' => $queue['symptoms'],
                    'isQueueEntry' => true,
                    'queueId' => $queue['queueId'],
                    'queueNumber' => $queue['queueNumber'],
                    'isGroupConsultation' => false
                ];
            }
        }
        // Add grouped group consultations to ongoingData
        foreach ($groupedQueues as $group) {
            $ongoingData[] = $group;
        }

        // Sort by queue number and consultation date
        usort($ongoingData, function($a, $b) {
            if ($a['isQueueEntry'] && $b['isQueueEntry']) {
                return strcmp($a['queueNumber'], $b['queueNumber']);
            } elseif ($a['isQueueEntry']) {
                return -1; // Queue entries first
            } elseif ($b['isQueueEntry']) {
                return 1;
            } else {
                return strcmp($a['consultationDate'], $b['consultationDate']);
            }
        });
            
        // Get total consultations for today for the summary card
        $todayTotal = $this->entityManager->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.consultationDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'ongoing' => $ongoingData,
            'todayTotal' => $todayTotal
        ];
    }

    #[Route('/{id}', name: 'app_consultations_get', methods: ['GET'])]
    public function get(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $consultation = $this->entityManager->getRepository(Consultation::class)->find($id);
            
            if (!$consultation) {
                return new JsonResponse(['message' => 'Consultation not found'], 404);
            }

            $patient = $consultation->getPatient();
            $doctor = $consultation->getDoctor();
            
            return new JsonResponse([
                'id' => $consultation->getId(),
                'patientId' => $patient->getId(),
                'patientName' => $patient->getName(),
                'doctorId' => $doctor->getId(),
                'doctorName' => $doctor->getName(),
                'createdAt' => $consultation->getCreatedAt()->format('Y-m-d H:i:s'),
                'symptoms' => $consultation->getSymptoms(),
                'diagnosis' => $consultation->getDiagnosis(),
                'treatment' => $consultation->getTreatment(),
                'consultationFee' => $consultation->getConsultationFee(),
                'medicinesFee' => $consultation->getMedicinesFee(),
                'totalAmount' => $consultation->getTotalAmount(),
                'isPaid' => $consultation->getIsPaid(),
                'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null,
                'receiptNumber' => method_exists($consultation, 'getReceiptNumber') ? $consultation->getReceiptNumber() : null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching consultation: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch consultation'], 500);
        }
    }

    #[Route('/{id}/payment', name: 'app_consultation_payment', methods: ['POST'])]
    public function updatePaymentStatus(
        int $id,
        Request $request
    ): JsonResponse {
        try {
            $consultation = $this->entityManager->getRepository(Consultation::class)->find($id);
            
            if (!$consultation) {
                return new JsonResponse(['message' => 'Consultation not found'], 404);
            }
            
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['isPaid'])) {
                return new JsonResponse(['error' => 'isPaid field is required'], 400);
            }
            
            $consultation->setIsPaid($data['isPaid']);
            
            // Set payment timestamp if marking as paid
            if ($data['isPaid']) {
                $myt = new \DateTimeZone('Asia/Kuala_Lumpur');
                $consultation->setPaidAt(new \DateTime('now', $myt));
            } else {
                $consultation->setPaidAt(null);
            }
            
            // Update payment method if provided
            if (isset($data['paymentMethod'])) {
                if (method_exists($consultation, 'setPaymentMethod')) {
                    $consultation->setPaymentMethod($data['paymentMethod']);
                }
            }
            
            // Also update the consultation status
            if ($consultation->getStatus() === 'completed_consultation') {
                $consultation->setStatus('completed');
            }
            
            $this->entityManager->flush();
            
            // Clear relevant caches
            if ($this->cache) {
                $this->cache->delete('consultations_list_' . date('Y-m-d'));
                $this->cache->delete('patient_history_' . $consultation->getPatient()->getId());
            }
            
            return new JsonResponse([
                'message' => 'Payment status updated successfully',
                'isPaid' => $consultation->getIsPaid(),
                'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error updating payment status: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Error updating payment status'], 500);
        }
    }
}
