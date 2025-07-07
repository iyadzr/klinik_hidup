<?php

namespace App\Controller;

use App\Entity\Queue;
use App\Entity\Patient;
use App\Entity\Doctor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/queue')]
class QueueController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ?CacheInterface $cache;
    
    // Rate limiting storage
    private static array $requestCounts = [];
    private static int $maxRequestsPerMinute = 60; // Higher limit for queue operations

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

    #[Route('', name: 'app_queue_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        $startTime = microtime(true);
        
        try {
            $date = $request->query->get('date');
            $status = $request->query->get('status');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(100, max(10, (int) $request->query->get('limit', 50))); // Max 100, min 10
            
            // Enhanced caching with request fingerprint
            $requestFingerprint = md5($date . $status . $page . $limit . $request->getClientIp());
            $cacheKey = 'queue_list_' . $requestFingerprint;
            
            // Set connection timeout for this request
            $connection = $this->entityManager->getConnection();
            $originalTimeout = $connection->executeQuery('SELECT @@wait_timeout')->fetchOne();
            $connection->executeStatement('SET SESSION wait_timeout = 30');
            
            try {
                if ($this->cache) {
                    $queueData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($date, $status, $page, $limit) {
                        $item->expiresAfter(3); // Reduced to 3 seconds for better real-time updates
                        return $this->buildQueueList($date, $status, $page, $limit);
                    });
                } else {
                    $queueData = $this->buildQueueList($date, $status, $page, $limit);
                }
                
                $executionTime = microtime(true) - $startTime;
                
                // Log slow requests
                if ($executionTime > 1.0) {
                    $this->logger->warning('Slow queue list request', [
                        'execution_time' => $executionTime,
                        'date' => $date,
                        'status' => $status,
                        'page' => $page,
                        'limit' => $limit,
                        'result_count' => count($queueData)
                    ]);
                }
                
                // Add performance headers
                $response = new JsonResponse($queueData);
                $response->headers->set('X-Execution-Time', round($executionTime * 1000, 2) . 'ms');
                $response->headers->set('X-Cache-Key', $cacheKey);
                
                return $response;
                
            } finally {
                // Restore original timeout
                $connection->executeStatement("SET SESSION wait_timeout = {$originalTimeout}");
            }
            
        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            $this->logger->error('Error fetching queue list', [
                'error' => $e->getMessage(),
                'execution_time' => $executionTime,
                'date' => $request->query->get('date'),
                'status' => $request->query->get('status'),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a more helpful error response
            return new JsonResponse([
                'error' => 'Failed to fetch queue data',
                'details' => $e->getMessage(),
                'execution_time' => round($executionTime * 1000, 2) . 'ms'
            ], 500);
        }
    }
    
    private function buildQueueList(?string $date, ?string $status, int $page, int $limit): array
    {
        try {
            $qb = $this->entityManager->getRepository(Queue::class)
                ->createQueryBuilder('q')
                ->select('q', 'p', 'd') // Select full objects to access entity methods
                ->leftJoin('q.patient', 'p') // Use LEFT JOIN instead of JOIN for safety
                ->leftJoin('q.doctor', 'd')
                ->addSelect('partial p.{id,name,nric,gender,phone,dateOfBirth,address}') // Only select needed patient fields
                ->addSelect('partial d.{id,name}'); // Only select needed doctor fields

            // Date filtering with proper timezone handling and index usage
            if ($date) {
                $startOfDay = \App\Service\TimezoneService::createDateTime($date . ' 00:00:00');
                $endOfDay = \App\Service\TimezoneService::createDateTime($date . ' 23:59:59');
                $qb->andWhere('q.queueDateTime >= :startDate AND q.queueDateTime <= :endDate')
                   ->setParameter('startDate', $startOfDay)
                   ->setParameter('endDate', $endOfDay);
            } else {
                // If no date specified, default to today to use index efficiently
                $today = \App\Service\TimezoneService::now();
                $startOfDay = (clone $today)->setTime(0, 0, 0);
                $endOfDay = (clone $today)->setTime(23, 59, 59);
                $qb->andWhere('q.queueDateTime >= :startDate AND q.queueDateTime <= :endDate')
                   ->setParameter('startDate', $startOfDay)
                   ->setParameter('endDate', $endOfDay);
            }

            // Status filtering with index usage
            if ($status && $status !== 'all') {
                $qb->andWhere('q.status = :status')
                   ->setParameter('status', $status);
            } else {
                // Filter out cancelled items by default for better performance
                $qb->andWhere('q.status != :cancelled')
                   ->setParameter('cancelled', 'cancelled');
            }

            // Add pagination to prevent memory issues
            $offset = ($page - 1) * $limit;
            $qb->setFirstResult($offset)
               ->setMaxResults($limit)
               ->orderBy('q.queueDateTime', 'DESC')
               ->addOrderBy('q.queueNumber', 'ASC');

            // Set query hints for performance
            $query = $qb->getQuery();
            $query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setHint(\Doctrine\ORM\Query::HINT_READ_ONLY, true);
            
            // Execute with timeout protection
            $startTime = microtime(true);
            $queues = $query->getResult();
            $queryTime = microtime(true) - $startTime;
            
            // Log slow queries
            if ($queryTime > 2.0) {
                $this->logger->warning('Slow queue query detected', [
                    'query_time' => $queryTime,
                    'date' => $date,
                    'status' => $status,
                    'page' => $page,
                    'limit' => $limit
                ]);
            }

            $queueData = [];
            $groupedQueues = [];

            foreach ($queues as $queue) {
                $patient = $queue->getPatient();
                $doctor = $queue->getDoctor();

                if (!$patient || !$doctor) {
                    continue; // Skip incomplete records
                }

                // Check if this is a group consultation using Queue entity methods
                $groupId = $queue->getGroupId();
                $isGroupConsultation = $queue->isGroupConsultation();

                if ($groupId && $isGroupConsultation && !isset($groupedQueues[$groupId])) {
                    // Group consultation: collect all patients in this group with optimized query
                    $groupQb = $this->entityManager->getRepository(Queue::class)
                        ->createQueryBuilder('gq')
                        ->select('gq.id, gq.metadata, gp.id as patientId, gp.name as patientName') // Select minimal fields
                        ->leftJoin('gq.patient', 'gp')
                        ->where('gq.metadata LIKE :groupId')
                        ->setParameter('groupId', '%"groupId":"' . $groupId . '"%')
                        ->setMaxResults(10); // Reduced limit for better performance
                    
                    $groupData = $groupQb->getQuery()->getScalarResult();
                    
                    $patients = [];
                    foreach ($groupData as $row) {
                        if ($row['patientId']) {
                            $patients[] = [
                                'id' => $row['patientId'],
                                'name' => $row['patientName'],
                                'displayName' => $row['patientName']
                            ];
                        }
                    }

                    $groupedQueues[$groupId] = [
                        'id' => $queue->getId(),
                        'queueNumber' => $queue->getQueueNumber(),
                        'registrationNumber' => $queue->getRegistrationNumber(),
                        'isGroupConsultation' => true,
                        'groupId' => $groupId,
                        'patients' => $patients,
                        'patientCount' => count($patients),
                        'mainPatient' => !empty($patients) ? $patients[0] : null,
                        'doctor' => [
                            'id' => $doctor->getId(),
                            'name' => $doctor->getName(),
                            'displayName' => method_exists($doctor, 'getDisplayName') ? $doctor->getDisplayName() : $doctor->getName()
                        ],
                        'status' => $queue->getStatus(),
                        'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s'),
                        'time' => $queue->getQueueDateTime()->format('d M Y, h:i:s a'),
                        'isPaid' => $queue->getIsPaid(),
                        'paidAt' => $queue->getPaidAt()?->format('Y-m-d H:i:s'),
                        'paymentMethod' => $queue->getPaymentMethod(),
                        'amount' => $this->getQueueTotalAmount($queue),
                        'totalAmount' => $this->getQueueTotalAmount($queue),
                        'consultationId' => $this->getQueueConsultationId($queue),
                        'hasMedicines' => $this->checkQueueHasMedicines($queue)
                    ];
                    $queueData[] = $groupedQueues[$groupId];
                } else if (!$isGroupConsultation) {
                    // Individual consultation
                    $queueData[] = [
                        'id' => $queue->getId(),
                        'queueNumber' => $queue->getQueueNumber(),
                        'registrationNumber' => $queue->getRegistrationNumber(),
                        'isGroupConsultation' => false,
                        'patient' => [
                            'id' => $patient->getId(),
                            'name' => $patient->getName(),
                            'displayName' => method_exists($patient, 'getDisplayName') ? $patient->getDisplayName() : $patient->getName()
                        ],
                        'doctor' => [
                            'id' => $doctor->getId(),
                            'name' => $doctor->getName(),
                            'displayName' => method_exists($doctor, 'getDisplayName') ? $doctor->getDisplayName() : $doctor->getName()
                        ],
                        'status' => $queue->getStatus(),
                        'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s'),
                        'time' => $queue->getQueueDateTime()->format('d M Y, h:i:s a'),
                        'isPaid' => $queue->getIsPaid(),
                        'paidAt' => $queue->getPaidAt()?->format('Y-m-d H:i:s'),
                        'paymentMethod' => $queue->getPaymentMethod(),
                        'amount' => $this->getQueueTotalAmount($queue),
                        'totalAmount' => $this->getQueueTotalAmount($queue),
                        'consultationId' => $this->getQueueConsultationId($queue),
                        'hasMedicines' => $this->checkQueueHasMedicines($queue)
                    ];
                }
            }

            return $queueData;
            
        } catch (\Exception $e) {
            $this->logger->error('Error building queue list', [
                'error' => $e->getMessage(),
                'date' => $date,
                'status' => $status,
                'page' => $page,
                'limit' => $limit
            ]);
            throw $e;
        }
    }
    
    /**
     * Get total amount for queue - from queue amount or consultation totalAmount
     */
    private function getQueueTotalAmount(Queue $queue): ?string
    {
        // First try to get amount from queue itself
        if ($queue->getAmount() !== null && $queue->getAmount() !== '0.00') {
            return $queue->getAmount();
        }
        
        // If queue amount is missing, get from consultation
        $consultation = $this->getQueueConsultation($queue);
        if ($consultation && $consultation->getTotalAmount() !== '0.00') {
            return $consultation->getTotalAmount();
        }
        
        return null;
    }

    /**
     * Get consultation for a queue entry
     */
    private function getQueueConsultation(Queue $queue): ?\App\Entity\Consultation
    {
        // Only look for consultations that are likely related to this queue session
        // by checking consultations created on the same date as the queue
        $queueDate = $queue->getQueueDateTime();
        $startOfDay = $queueDate->format('Y-m-d 00:00:00');
        $endOfDay = $queueDate->format('Y-m-d 23:59:59');
        
        return $this->entityManager->getRepository(\App\Entity\Consultation::class)
            ->createQueryBuilder('c')
            ->where('c.patient = :patient')
            ->andWhere('c.doctor = :doctor')
            ->andWhere('c.consultationDate >= :startOfDay')
            ->andWhere('c.consultationDate <= :endOfDay')
            ->andWhere('c.status IN (:validStatuses)')
            ->setParameter('patient', $queue->getPatient())
            ->setParameter('doctor', $queue->getDoctor())
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('validStatuses', ['completed_consultation', 'completed'])
            ->orderBy('c.consultationDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get consultation ID for a queue entry
     */
    private function getQueueConsultationId(Queue $queue): ?int
    {
        // Only look for consultations that are likely related to this queue session
        // by checking consultations created on the same date as the queue
        $queueDate = $queue->getQueueDateTime();
        $startOfDay = $queueDate->format('Y-m-d 00:00:00');
        $endOfDay = $queueDate->format('Y-m-d 23:59:59');
        
        $consultation = $this->entityManager->getRepository(\App\Entity\Consultation::class)
            ->createQueryBuilder('c')
            ->where('c.patient = :patient')
            ->andWhere('c.doctor = :doctor')
            ->andWhere('c.consultationDate >= :startOfDay')
            ->andWhere('c.consultationDate <= :endOfDay')
            ->andWhere('c.status IN (:validStatuses)')
            ->setParameter('patient', $queue->getPatient())
            ->setParameter('doctor', $queue->getDoctor())
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('validStatuses', ['completed_consultation', 'completed'])
            ->orderBy('c.consultationDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
            
        return $consultation ? $consultation->getId() : null;
    }
    
    /**
     * Check if queue has prescribed medicines
     */
    private function checkQueueHasMedicines(Queue $queue): bool
    {
        // Only check for medicines if the queue has been processed (consultation completed)
        // This prevents showing medicines for queues that haven't started consultation yet
        if (!in_array($queue->getStatus(), ['completed_consultation', 'completed'])) {
            return false;
        }
        
        $consultationId = $this->getQueueConsultationId($queue);
        if (!$consultationId) {
            return false;
        }
        
        // Check if there are any prescribed medications for this consultation
        $prescribedMeds = $this->entityManager->getRepository(\App\Entity\PrescribedMedication::class)
            ->createQueryBuilder('pm')
            ->where('pm.consultation = :consultationId')
            ->setParameter('consultationId', $consultationId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
            
        if ($prescribedMeds) {
            return true;
        }
        
        // Also check for medicines in the consultation medications field (JSON)
        $consultation = $this->entityManager->getRepository(\App\Entity\Consultation::class)->find($consultationId);
        if ($consultation && $consultation->getMedications()) {
            $medications = json_decode($consultation->getMedications(), true);
            if (is_array($medications) && !empty($medications)) {
                return true;
            }
        }
        
        return false;
    }

    #[Route('', name: 'app_queue_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }
            
            // Validate required fields
            if (!isset($data['patientId']) || !isset($data['doctorId'])) {
                return new JsonResponse(['error' => 'Patient ID and Doctor ID are required'], 400);
            }
            
            $patient = $this->entityManager->getRepository(Patient::class)->find($data['patientId']);
            $doctor = $this->entityManager->getRepository(Doctor::class)->find($data['doctorId']);
            
            if (!$patient) {
                return new JsonResponse(['error' => 'Patient not found'], 404);
            }
            
            if (!$doctor) {
                return new JsonResponse(['error' => 'Doctor not found'], 404);
            }
            
            $queue = new Queue();
            $queue->setPatient($patient);
            $queue->setDoctor($doctor);
            $queue->setStatus('waiting');
            
            // Assign queue number based on registration time and running number for the hour
            $queueDateTime = \App\Service\TimezoneService::nowImmutable();
            $queue->setQueueDateTime($queueDateTime);
            $hour = (int)$queueDateTime->format('G'); // 0-23, e.g., 8, 9, 15
            
            // Find the latest queue number for this hour block today
            $qb = $this->entityManager->getRepository(Queue::class)->createQueryBuilder('q');
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
            
            // Handle group consultation
            if (isset($data['isGroupConsultation']) && $data['isGroupConsultation']) {
                $metadata = [
                    'isGroupConsultation' => true,
                    'groupId' => $data['groupId'] ?? uniqid()
                ];
                $queue->setMetadata(json_encode($metadata));
            }
            
            // Override registration number if provided
            if (isset($data['registrationNumber'])) {
                $queue->setRegistrationNumber($data['registrationNumber']);
            }
            
            $this->entityManager->persist($queue);
            $this->entityManager->flush();
            
            // Clear relevant caches
            if ($this->cache) {
                $this->cache->delete('queue_list_' . date('Y-m-d') . '_all_page_1_limit_50');
                $this->cache->delete('queue_stats');
            }
            
            // Broadcast SSE update for new queue entry
            $this->broadcastQueueUpdate($queue);
            
            $this->logger->info('Queue entry created', [
                'queueId' => $queue->getId(),
                'queueNumber' => $queueNumber,
                'registrationNumber' => $queue->getRegistrationNumber(),
                'patientId' => $patient->getId(),
                'doctorId' => $doctor->getId()
            ]);
            
            return new JsonResponse([
                'id' => $queue->getId(),
                'queueNumber' => $queueNumber,
                'registrationNumber' => $queue->getRegistrationNumber(),
                'message' => 'Queue entry created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating queue entry', [
                'error' => $e->getMessage(),
                'data' => $data ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'error' => 'Failed to create queue entry',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/group', name: 'app_queue_create_group', methods: ['POST'])]
    public function createGroup(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }
            
            // Validate required fields
            if (!isset($data['patients']) || !isset($data['doctorId'])) {
                return new JsonResponse(['error' => 'Patients array and Doctor ID are required'], 400);
            }
            
            if (!is_array($data['patients']) || empty($data['patients'])) {
                return new JsonResponse(['error' => 'Patients array cannot be empty'], 400);
            }
            
            $doctor = $this->entityManager->getRepository(Doctor::class)->find($data['doctorId']);
            
            if (!$doctor) {
                return new JsonResponse(['error' => 'Doctor not found'], 404);
            }
            
            $groupId = uniqid('group_');
            $createdQueues = [];
            
            // Set queue date/time
            $queueDateTime = isset($data['queueDateTime']) 
                ? \App\Service\TimezoneService::createDateTimeImmutable($data['queueDateTime'])
                : \App\Service\TimezoneService::nowImmutable();
            
            foreach ($data['patients'] as $patientData) {
                if (!isset($patientData['id'])) {
                    return new JsonResponse(['error' => 'Each patient must have an ID'], 400);
                }
                
                $patient = $this->entityManager->getRepository(Patient::class)->find($patientData['id']);
                
                if (!$patient) {
                    return new JsonResponse(['error' => "Patient with ID {$patientData['id']} not found"], 404);
                }
                
                $queue = new Queue();
                $queue->setPatient($patient);
                $queue->setDoctor($doctor);
                $queue->setStatus('waiting');
                $queue->setQueueDateTime($queueDateTime);
                
                // Generate registration number based on hour
                $hour = (int)$queueDateTime->format('G'); // 0-23, e.g., 8, 9, 15
                
                // Find the latest queue number for this hour block today
                $qb = $this->entityManager->getRepository(Queue::class)->createQueryBuilder('q');
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
                
                // Set group consultation metadata
                $metadata = [
                    'isGroupConsultation' => true,
                    'groupId' => $groupId,
                    'relationship' => $patientData['relationship'] ?? null
                ];
                $queue->setMetadata(json_encode($metadata));
                
                $this->entityManager->persist($queue);
                
                $createdQueues[] = [
                    'queueId' => null, // Will be set after flush
                    'queueNumber' => $queueNumber,
                    'registrationNumber' => $queue->getRegistrationNumber(),
                    'patientId' => $patient->getId(),
                    'patientName' => $patient->getName(),
                    'relationship' => $patientData['relationship'] ?? null
                ];
            }
            
            $this->entityManager->flush();
            
            // Update queue IDs after flush
            $queues = $this->entityManager->getRepository(Queue::class)
                ->findBy(['doctor' => $doctor], ['id' => 'DESC'], count($createdQueues));
            
            for ($i = 0; $i < count($createdQueues); $i++) {
                if (isset($queues[$i])) {
                    $createdQueues[$i]['queueId'] = $queues[$i]->getId();
                }
            }
            
            // Clear relevant caches
            if ($this->cache) {
                $this->cache->delete('queue_list_' . date('Y-m-d') . '_all_page_1_limit_50');
                $this->cache->delete('queue_stats');
            }
            
            // Broadcast SSE updates for all created queue entries
            foreach ($queues as $queue) {
                $this->broadcastQueueUpdate($queue);
            }
            
            $this->logger->info('Group queue entries created', [
                'groupId' => $groupId,
                'patientCount' => count($createdQueues),
                'doctorId' => $doctor->getId()
            ]);
            
            return new JsonResponse([
                'groupId' => $groupId,
                'patients' => $createdQueues,
                'message' => 'Group queue entries created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating group queue entries', [
                'error' => $e->getMessage(),
                'data' => $data ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'error' => 'Failed to create group queue entries',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/status', name: 'app_queue_update_status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $queue = $this->entityManager->getRepository(Queue::class)->find($id);
            
            if (!$queue) {
                return new JsonResponse(['error' => 'Queue entry not found'], 404);
            }
            
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['status'])) {
                return new JsonResponse(['error' => 'Status is required'], 400);
            }
            
            $allowedStatuses = ['waiting', 'in_consultation', 'completed_consultation', 'completed', 'cancelled'];
            if (!in_array($data['status'], $allowedStatuses)) {
                return new JsonResponse(['error' => 'Invalid status'], 400);
            }
            
            $oldStatus = $queue->getStatus();
            $queue->setStatus($data['status']);
            $this->entityManager->flush();
            
            // Clear relevant caches with more granular keys
            if ($this->cache) {
                $today = date('Y-m-d');
                $cacheKeys = [
                    // Queue-specific caches
                    'queue_list_' . $today . '_all_page_1_limit_50',
                    'queue_list_' . $today . '_waiting_page_1_limit_50',
                    'queue_list_' . $today . '_in_consultation_page_1_limit_50',
                    'queue_list_' . $today . '_completed_page_1_limit_50',
                    'queue_list_' . $today . '_completed_consultation_page_1_limit_50',
                    'queue_stats',
                    'queue_stats_' . $today,
                    // Doctor-specific caches
                    'queue_list_doctor_' . $queue->getDoctor()->getId() . '_' . $today,
                    // Patient-specific caches
                    'patient_queue_' . $queue->getPatient()->getId() . '_' . $today,
                    // Status-specific caches
                    'queue_status_' . $oldStatus . '_' . $today,
                    'queue_status_' . $data['status'] . '_' . $today
                ];
                
                foreach ($cacheKeys as $key) {
                    $this->cache->delete($key);
                }
                
                $this->logger->debug('Cache invalidated for status update', [
                    'queueId' => $id,
                    'oldStatus' => $oldStatus,
                    'newStatus' => $data['status'],
                    'keysCleared' => count($cacheKeys)
                ]);
            }
            
            // Broadcast SSE update for status change
            $this->broadcastQueueUpdate($queue);
            
            // Broadcast pending actions update when consultation is completed
            if ($data['status'] === 'completed_consultation') {
                $this->broadcastPendingActionsUpdate();
            }
            
            $this->logger->info('Queue status updated', [
                'queueId' => $id,
                'oldStatus' => $oldStatus,
                'newStatus' => $data['status']
            ]);
            
            return new JsonResponse([
                'message' => 'Queue status updated successfully',
                'status' => $queue->getStatus()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error updating queue status: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to update queue status'], 500);
        }
    }

    #[Route('/{id}/payment', name: 'app_queue_payment', methods: ['POST'])]
    public function processPayment(int $id, Request $request): JsonResponse
    {
        try {
            $queue = $this->entityManager->getRepository(Queue::class)->find($id);
            
            if (!$queue) {
                return new JsonResponse(['error' => 'Queue entry not found'], 404);
            }
            
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['paymentMethod']) || !isset($data['amount'])) {
                return new JsonResponse(['error' => 'Payment method and amount are required'], 400);
            }
            
            // Check if payment already processed
            if ($queue->getIsPaid()) {
                return new JsonResponse(['error' => 'Payment already processed for this queue'], 409);
            }
            
            // Get current user (who is processing the payment)
            $currentUser = $this->getUser();
            if (!$currentUser) {
                return new JsonResponse(['error' => 'User authentication required'], 401);
            }
            
            // Create Payment record for comprehensive tracking
            $payment = new \App\Entity\Payment();
            $payment->setAmount($data['amount']);
            $payment->setPaymentMethod($data['paymentMethod']);
            $payment->setProcessedBy($currentUser);
            $payment->setQueue($queue);
            $payment->setQueueNumber($queue->getQueueNumber());
            $payment->setReference('Q-' . $queue->getQueueNumber() . '-' . date('YmdHis'));
            
            // Link to consultation if exists
            $consultation = $this->getQueueConsultation($queue);
            if ($consultation) {
                $payment->setConsultation($consultation);
            }
            
            // Add notes with medicines information
            $medicines = $this->getQueueMedicinesInfo($queue);
            if ($medicines) {
                $medicinesText = implode(', ', array_map(function($med) {
                    return $med['name'] . ' (' . ($med['dosage'] ?? 'N/A') . ')';
                }, $medicines));
                $payment->setNotes('Medicines: ' . $medicinesText);
            }
            
            // Update queue payment status
            $queue->setIsPaid(true);
            $queue->setPaidAt(\App\Service\TimezoneService::nowImmutable());
            $queue->setPaymentMethod($data['paymentMethod']);
            $queue->setAmount($data['amount']);
            
            // CRITICAL: Update the payment method in the Payment entity too
            $payment->setPaymentMethod($data['paymentMethod']);
            
            // If status is 'completed_consultation', change it to 'completed'
            if ($queue->getStatus() === 'completed_consultation') {
                $queue->setStatus('completed');
            }
            
            // Persist both entities
            $this->entityManager->persist($payment);
            $this->entityManager->flush();
            
            // Clear relevant caches
            if ($this->cache) {
                $this->cache->delete('queue_list_' . date('Y-m-d') . '_all_page_1_limit_50');
                $this->cache->delete('queue_stats');
                $this->cache->delete('financial_dashboard');
                $this->cache->delete('payments_list');
            }
            
            // Broadcast SSE update for payment status change
            $this->broadcastQueueUpdate($queue);
            
            // Also broadcast payment-specific update
            $this->broadcastPaymentUpdate($payment, $queue);
            
            // Broadcast pending actions update to update notification badge
            $this->broadcastPendingActionsUpdate();
            
            $this->logger->info('Queue payment processed with Payment record', [
                'queueId' => $id,
                'paymentId' => $payment->getId(),
                'paymentMethod' => $data['paymentMethod'],
                'amount' => $data['amount'],
                'processedBy' => $currentUser instanceof \App\Entity\User ? $currentUser->getId() : 'Unknown'
            ]);
            
            return new JsonResponse([
                'message' => 'Payment processed successfully',
                'isPaid' => $queue->getIsPaid(),
                'paidAt' => $queue->getPaidAt()->format('Y-m-d H:i:s'),
                'status' => $queue->getStatus(),
                'paymentId' => $payment->getId(),
                'reference' => $payment->getReference()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error processing queue payment: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to process payment'], 500);
        }
    }
    
    private function getQueueMedicinesInfo(Queue $queue): array
    {
        try {
            $consultationId = $this->getQueueConsultationId($queue);
            if (!$consultationId) {
                return [];
            }
            
            $medications = $this->entityManager->getRepository(\App\Entity\PrescribedMedication::class)
                ->createQueryBuilder('pm')
                ->leftJoin('pm.medication', 'm')
                ->where('pm.consultation = :consultationId')
                ->setParameter('consultationId', $consultationId)
                ->getQuery()
                ->getResult();
            
            return array_map(function($prescribedMed) {
                $medication = $prescribedMed->getMedication();
                return [
                    'name' => $medication ? $medication->getName() : 'Unknown Medicine',
                    'dosage' => $prescribedMed->getDosage() ?? 'N/A',
                    'frequency' => $prescribedMed->getFrequency() ?? 'N/A',
                    'duration' => $prescribedMed->getDuration() ?? 'N/A'
                ];
            }, $medications);
        } catch (\Exception $e) {
            $this->logger->warning('Error fetching medicines info for payment: ' . $e->getMessage());
            return [];
        }
    }

    #[Route('/{queueNumber}/patients', name: 'app_queue_patients', methods: ['GET'])]
    public function getQueuePatients(string $queueNumber, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            // Find queue by queue number
            $queue = $this->entityManager->getRepository(Queue::class)
                ->findOneBy(['queueNumber' => $queueNumber]);
            
            if (!$queue) {
                return new JsonResponse(['error' => 'Queue not found'], Response::HTTP_NOT_FOUND);
            }
            
            $patients = [];
            
            if ($queue->isGroupConsultation()) {
                // Get all patients in the group
                $groupId = $queue->getGroupId();
                $groupQueues = $this->entityManager->getRepository(Queue::class)
                    ->createQueryBuilder('q')
                    ->leftJoin('q.patient', 'p')
                    ->where('q.metadata LIKE :groupId')
                    ->setParameter('groupId', '%"groupId":"' . $groupId . '"%')
                    ->getQuery()
                    ->getResult();
                
                foreach ($groupQueues as $groupQueue) {
                    $patient = $groupQueue->getPatient();
                    if ($patient) {
                        $patients[] = [
                            'id' => $patient->getId(),
                            'name' => $patient->getName(),
                            'displayName' => method_exists($patient, 'getDisplayName') ? $patient->getDisplayName() : $patient->getName(),
                            'nric' => $patient->getNric(),
                            'ic' => $patient->getNric(), // Alias for compatibility
                            'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                            'gender' => $patient->getGender(),
                            'phoneNumber' => $patient->getPhone(),
                            'phone' => $patient->getPhone(), // Alias for compatibility
                            'address' => $patient->getAddress(),
                            'symptoms' => $this->getQueueSymptoms($groupQueue),
                            'relationship' => $this->getPatientRelationship($groupQueue)
                        ];
                    }
                }
            } else {
                // Single patient
                $patient = $queue->getPatient();
                if ($patient) {
                    $patients[] = [
                        'id' => $patient->getId(),
                        'name' => $patient->getName(),
                        'displayName' => method_exists($patient, 'getDisplayName') ? $patient->getDisplayName() : $patient->getName(),
                        'nric' => $patient->getNric(),
                        'ic' => $patient->getNric(), // Alias for compatibility
                        'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                        'gender' => $patient->getGender(),
                        'phoneNumber' => $patient->getPhone(),
                        'phone' => $patient->getPhone(), // Alias for compatibility
                        'address' => $patient->getAddress(),
                                                    'symptoms' => $this->getQueueSymptoms($queue),
                        'relationship' => null
                    ];
                }
            }
            
            return new JsonResponse($patients);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching queue patients: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch queue patients'], 500);
        }
    }
    
    private function getPatientRelationship(Queue $queue): ?string
    {
        $metadata = $queue->getMetadata();
        if ($metadata) {
            $data = json_decode($metadata, true);
            return $data['relationship'] ?? null;
        }
        return null;
    }
    
    private function getQueueSymptoms(Queue $queue): ?string
    {
        // First check if symptoms are in queue metadata
        $metadata = $queue->getMetadata();
        if ($metadata) {
            $data = json_decode($metadata, true);
            if (isset($data['symptoms'])) {
                return $data['symptoms'];
            }
        }
        
        // Fallback to patient's remarks
        $patient = $queue->getPatient();
        if ($patient && method_exists($patient, 'getRemarks')) {
            return $patient->getRemarks();
        }
        
        return null;
    }

    #[Route('/stats', name: 'app_queue_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $cacheKey = 'queue_stats';
            
            if ($this->cache) {
                $stats = $this->cache->get($cacheKey, function (ItemInterface $item) {
                    $item->expiresAfter(300); // 5 minutes cache
                    return $this->buildQueueStats();
                });
            } else {
                $stats = $this->buildQueueStats();
            }
            
            return new JsonResponse($stats);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching queue stats: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch queue stats'], 500);
        }
    }
    
    private function buildQueueStats(): array
    {
        $today = \App\Service\TimezoneService::now();
        $todayStr = $today->format('Y-m-d');
        
        $repo = $this->entityManager->getRepository(Queue::class);
        
        // Count by status
        $waiting = $repo->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.status = :status AND DATE(q.queueDateTime) = :today')
            ->setParameter('status', 'waiting')
            ->setParameter('today', $todayStr)
            ->getQuery()
            ->getSingleScalarResult();
            
        $inConsultation = $repo->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.status = :status AND DATE(q.queueDateTime) = :today')
            ->setParameter('status', 'in_consultation')
            ->setParameter('today', $todayStr)
            ->getQuery()
            ->getSingleScalarResult();
            
        $completedConsultation = $repo->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.status = :status AND DATE(q.queueDateTime) = :today')
            ->setParameter('status', 'completed_consultation')
            ->setParameter('today', $todayStr)
            ->getQuery()
            ->getSingleScalarResult();
            
        $completed = $repo->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.status = :status AND DATE(q.queueDateTime) = :today')
            ->setParameter('status', 'completed')
            ->setParameter('today', $todayStr)
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'today' => [
                'waiting' => $waiting,
                'in_consultation' => $inConsultation,
                'completed_consultation' => $completedConsultation,
                'completed' => $completed,
                'total' => $waiting + $inConsultation + $completedConsultation + $completed
            ],
            'timestamp' => $today->format('Y-m-d H:i:s')
        ];
    }

    #[Route('/{id}', name: 'app_queue_get', methods: ['GET'])]
    public function getById(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $queue = $this->entityManager->getRepository(Queue::class)->find($id);
            
            if (!$queue) {
                return new JsonResponse(['error' => 'Queue entry not found'], 404);
            }
            
            $patient = $queue->getPatient();
            $doctor = $queue->getDoctor();
            
            if (!$patient || !$doctor) {
                return new JsonResponse(['error' => 'Incomplete queue data'], 400);
            }
            
            // Get the most current remarks for the patient
            $patientRemarks = $this->getQueueSymptoms($queue);
            if (empty($patientRemarks) && method_exists($patient, 'getRemarks')) {
                $patientRemarks = $patient->getRemarks();
            }
            $patientRemarks = $patientRemarks ?: null;
            
            $queueData = [
                'id' => $queue->getId(),
                'queueNumber' => $queue->getQueueNumber(),
                'registrationNumber' => $queue->getRegistrationNumber(),
                'isGroupConsultation' => $queue->isGroupConsultation(),
                'groupId' => $queue->getGroupId(),
                'patient' => [
                    'id' => $patient->getId(),
                    'name' => $patient->getName(),
                    'displayName' => method_exists($patient, 'getDisplayName') ? $patient->getDisplayName() : $patient->getName(),
                    'nric' => $patient->getNric(),
                    'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                    'gender' => $patient->getGender(),
                    'phone' => $patient->getPhone(),
                    'address' => $patient->getAddress(),
                    'remarks' => $patientRemarks
                ],
                'doctor' => [
                    'id' => $doctor->getId(),
                    'name' => $doctor->getName(),
                    'displayName' => method_exists($doctor, 'getDisplayName') ? $doctor->getDisplayName() : $doctor->getName()
                ],
                'status' => $queue->getStatus(),
                'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s'),
                'isPaid' => $queue->getIsPaid(),
                'paidAt' => $queue->getPaidAt()?->format('Y-m-d H:i:s'),
                'paymentMethod' => $queue->getPaymentMethod(),
                'amount' => $this->getQueueTotalAmount($queue),
                'totalAmount' => $this->getQueueTotalAmount($queue),
                'metadata' => $queue->getMetadata()
            ];
            
            // If this is a group consultation, fetch all group members
            if ($queue->isGroupConsultation() && $queue->getGroupId()) {
                $groupId = $queue->getGroupId();
                
                // Fetch all patients in this group
                $groupMembers = $this->entityManager->getRepository(Queue::class)
                    ->createQueryBuilder('q')
                    ->leftJoin('q.patient', 'p')
                    ->where('q.metadata LIKE :groupId')
                    ->setParameter('groupId', '%"groupId":"' . $groupId . '"%')
                    ->getQuery()
                    ->getResult();
                
                $groupPatients = [];
                foreach ($groupMembers as $memberQueue) {
                    $memberPatient = $memberQueue->getPatient();
                    if ($memberPatient) {
                        // Get relationship from metadata
                        $metadata = $memberQueue->getMetadata();
                        $relationship = 'N/A';
                        if ($metadata) {
                            $metadataArray = json_decode($metadata, true);
                            $relationship = $metadataArray['relationship'] ?? 'N/A';
                        }
                        
                        // Get the most current remarks - check queue metadata first, then patient
                        $remarks = $this->getQueueSymptoms($memberQueue);
                        if (!$remarks && method_exists($memberPatient, 'getRemarks')) {
                            $remarks = $memberPatient->getRemarks();
                        }
                        
                        $groupPatients[] = [
                            'id' => $memberPatient->getId(),
                            'name' => $memberPatient->getName(),
                            'displayName' => method_exists($memberPatient, 'getDisplayName') ? $memberPatient->getDisplayName() : $memberPatient->getName(),
                            'nric' => $memberPatient->getNric(),
                            'dateOfBirth' => $memberPatient->getDateOfBirth()?->format('Y-m-d'),
                            'gender' => $memberPatient->getGender(),
                            'phone' => $memberPatient->getPhone(),
                            'address' => $memberPatient->getAddress(),
                            'relationship' => $relationship,
                            'remarks' => $remarks
                        ];
                    }
                }
                
                $queueData['groupPatients'] = $groupPatients;
                $queueData['patientCount'] = count($groupPatients);
            }
            
            return new JsonResponse($queueData);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching queue entry: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch queue entry'], 500);
        }
    }

    #[Route('/{id}/medications', name: 'app_queue_medications', methods: ['GET'])]
    public function getQueueMedications(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $queue = $this->entityManager->getRepository(Queue::class)->find($id);
            
            if (!$queue) {
                return new JsonResponse(['message' => 'Queue entry not found'], 404);
            }
            
            $consultationId = $this->getQueueConsultationId($queue);
            if (!$consultationId) {
                return new JsonResponse([]); // Return empty array if no consultation found
            }
            
            // Get prescribed medications from PrescribedMedication entities
            $prescribedMeds = $this->entityManager->getRepository(\App\Entity\PrescribedMedication::class)
                ->createQueryBuilder('pm')
                ->leftJoin('pm.medication', 'm')
                ->where('pm.consultation = :consultationId')
                ->setParameter('consultationId', $consultationId)
                ->orderBy('pm.prescribedAt', 'ASC')
                ->getQuery()
                ->getResult();
            
            $medicationsData = [];
            
            if (!empty($prescribedMeds)) {
                // Use prescribed medications entities
                foreach ($prescribedMeds as $prescribedMed) {
                    $medication = $prescribedMed->getMedication();
                    $medicationsData[] = [
                        'name' => $medication ? $medication->getName() : $prescribedMed->getName(),
                        'medicationName' => $medication ? $medication->getName() : $prescribedMed->getName(),
                        'dosage' => $prescribedMed->getDosage(),
                        'frequency' => $prescribedMed->getFrequency(),
                        'duration' => $prescribedMed->getDuration(),
                        'instructions' => $prescribedMed->getInstructions(),
                        'quantity' => $prescribedMed->getQuantity(),
                        'unitType' => $medication ? $medication->getUnitType() : 'pieces',
                        'actualPrice' => $prescribedMed->getActualPrice()
                    ];
                }
            } else {
                // Fallback to medications JSON field
                $consultation = $this->entityManager->getRepository(\App\Entity\Consultation::class)->find($consultationId);
                if ($consultation && $consultation->getMedications()) {
                    $medications = json_decode($consultation->getMedications(), true);
                    if (is_array($medications)) {
                        foreach ($medications as $med) {
                            $medicationsData[] = [
                                'name' => $med['name'] ?? 'Unknown Medicine',
                                'medicationName' => $med['name'] ?? 'Unknown Medicine',
                                'dosage' => $med['dosage'] ?? '-',
                                'frequency' => $med['frequency'] ?? '-',
                                'duration' => $med['duration'] ?? '-',
                                'instructions' => $med['instructions'] ?? '-',
                                'quantity' => $med['quantity'] ?? 1,
                                'unitType' => $med['unitType'] ?? 'pieces',
                                'actualPrice' => $med['actualPrice'] ?? 0
                            ];
                        }
                    }
                }
            }
            
            return new JsonResponse($medicationsData);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching queue medications: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch medications'], 500);
        }
    }

    #[Route('/{id}', name: 'app_queue_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $queue = $this->entityManager->getRepository(Queue::class)->find($id);
            
            if (!$queue) {
                return new JsonResponse(['error' => 'Queue entry not found'], 404);
            }
            
            $this->entityManager->remove($queue);
            $this->entityManager->flush();
            
            // Clear relevant caches
            if ($this->cache) {
                $this->cache->delete('queue_list_' . date('Y-m-d') . '_all_page_1_limit_50');
                $this->cache->delete('queue_stats');
            }
            
            $this->logger->info('Queue entry deleted', ['queueId' => $id]);
            
            return new JsonResponse(['message' => 'Queue entry deleted successfully']);
            
        } catch (\Exception $e) {
            $this->logger->error('Error deleting queue entry: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to delete queue entry'], 500);
        }
    }

    /**
     * Broadcast queue update via SSE
     */
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

    /**
     * Broadcast payment update via SSE
     */
    private function broadcastPaymentUpdate($payment, $queue): void
    {
        // Store the payment update in a temporary file for SSE to pick up
        $updateData = [
            'type' => 'payment_processed',
            'timestamp' => time(),
            'data' => [
                'payment_id' => $payment->getId(),
                'amount' => $payment->getAmount(),
                'payment_method' => $payment->getPaymentMethod(),
                'reference' => $payment->getReference(),
                'queue_id' => $queue->getId(),
                'queue_number' => $queue->getQueueNumber(),
                'patient' => [
                    'id' => $queue->getPatient()->getId(),
                    'name' => $queue->getPatient()->getName(),
                ],
                'doctor' => [
                    'id' => $queue->getDoctor()->getId(),
                    'name' => $queue->getDoctor()->getName(),
                ],
                'payment_date' => $payment->getPaymentDate()->format('Y-m-d H:i:s'),
                'processed_by' => [
                    'id' => $payment->getProcessedBy()->getId(),
                    'name' => $payment->getProcessedBy()->getName(),
                ]
            ]
        ];
        
        // Write to payment-specific temporary file
        $tempDir = sys_get_temp_dir();
        $paymentUpdateFile = $tempDir . '/payment_updates.json';
        
        // Read existing updates
        $updates = [];
        if (file_exists($paymentUpdateFile)) {
            $content = file_get_contents($paymentUpdateFile);
            $updates = json_decode($content, true) ?: [];
        }
        
        // Add new update
        $updates[] = $updateData;
        
        // Keep only last 10 updates to prevent file from growing too large
        $updates = array_slice($updates, -10);
        
        // Write back to file
        file_put_contents($paymentUpdateFile, json_encode($updates));
    }

    /**
     * Broadcast pending actions count update via SSE
     */
    private function broadcastPendingActionsUpdate(): void
    {
        try {
            $today = \App\Service\TimezoneService::now();
            $startOfDay = (clone $today)->setTime(0, 0, 0);
            $endOfDay = (clone $today)->setTime(23, 59, 59);
            
            // Count consultations that are completed but not paid (pending payment processing)
            $pendingPaymentCount = $this->entityManager->getRepository(Queue::class)
                ->createQueryBuilder('q')
                ->select('COUNT(q.id)')
                ->where('q.queueDateTime BETWEEN :start AND :end')
                ->andWhere('q.status = :status')
                ->andWhere('(q.isPaid = false OR q.isPaid IS NULL)')
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->setParameter('status', 'completed_consultation')
                ->getQuery()
                ->getSingleScalarResult();
            
            $updateData = [
                'type' => 'pending_actions_update',
                'timestamp' => time(),
                'data' => [
                    'pendingPayments' => (int) $pendingPaymentCount,
                    'totalPendingActions' => (int) $pendingPaymentCount
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
            
        } catch (\Exception $e) {
            $this->logger->error('Error broadcasting pending actions update: ' . $e->getMessage());
        }
    }

    #[Route('/pending-actions', name: 'app_queue_pending_actions', methods: ['GET'])]
    public function getPendingActions(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $today = \App\Service\TimezoneService::now();
            $startOfDay = (clone $today)->setTime(0, 0, 0);
            $endOfDay = (clone $today)->setTime(23, 59, 59);
            
            // Count consultations that are completed but not paid (pending payment processing)
            $pendingPaymentCount = $this->entityManager->getRepository(Queue::class)
                ->createQueryBuilder('q')
                ->select('COUNT(q.id)')
                ->where('q.queueDateTime BETWEEN :start AND :end')
                ->andWhere('q.status = :status')
                ->andWhere('(q.isPaid = false OR q.isPaid IS NULL)')
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->setParameter('status', 'completed_consultation')
                ->getQuery()
                ->getSingleScalarResult();
            
            return new JsonResponse([
                'pendingPayments' => (int) $pendingPaymentCount,
                'totalPendingActions' => (int) $pendingPaymentCount
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching pending actions: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch pending actions'], 500);
        }
    }
}
