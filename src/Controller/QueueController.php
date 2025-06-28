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
        
        try {
            $date = $request->query->get('date');
            $status = $request->query->get('status');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(100, max(10, (int) $request->query->get('limit', 50))); // Max 100, min 10
            
            $cacheKey = 'queue_list_' . ($date ?: 'all') . '_' . ($status ?: 'all') . '_page_' . $page . '_limit_' . $limit;
            
            if ($this->cache) {
                $queueData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($date, $status, $page, $limit) {
                    $item->expiresAfter(60); // 1 minute cache for queue data
                    return $this->buildQueueList($date, $status, $page, $limit);
                });
            } else {
                $queueData = $this->buildQueueList($date, $status, $page, $limit);
            }
            
            return new JsonResponse($queueData);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching queue list: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch queue data'], 500);
        }
    }
    
    private function buildQueueList(?string $date, ?string $status, int $page, int $limit): array
    {
        $qb = $this->entityManager->getRepository(Queue::class)
            ->createQueryBuilder('q')
            ->select('q', 'p', 'd') // Select related entities to avoid N+1 queries
            ->leftJoin('q.patient', 'p')
            ->leftJoin('q.doctor', 'd')
            ->orderBy('q.queueDateTime', 'DESC');
        
        // Apply date filter
        if ($date) {
            $start = new \DateTime($date . ' 00:00:00', new \DateTimeZone('Asia/Kuala_Lumpur'));
            $end = new \DateTime($date . ' 23:59:59', new \DateTimeZone('Asia/Kuala_Lumpur'));
            
            $qb->where('q.queueDateTime BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        } else {
            // Default to last 7 days for performance
            $sevenDaysAgo = new \DateTime('-7 days', new \DateTimeZone('Asia/Kuala_Lumpur'));
            $qb->where('q.queueDateTime >= :sevenDaysAgo')
               ->setParameter('sevenDaysAgo', $sevenDaysAgo);
        }
        
        // Apply status filter
        if ($status) {
            $qb->andWhere('q.status = :status')
               ->setParameter('status', $status);
        }
        
        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        
        $queues = $qb->getQuery()->getResult();
        
        $queueData = [];
        $groupedQueues = [];
        
        foreach ($queues as $queue) {
            $patient = $queue->getPatient();
            $doctor = $queue->getDoctor();
            
            if (!$patient || !$doctor) {
                continue; // Skip incomplete records
            }
            
            $groupId = $queue->getGroupId();
            
            if ($groupId && $queue->isGroupConsultation()) {
                // Group consultation handling
                if (!isset($groupedQueues[$groupId])) {
                    // Fetch all patients in this group
                    $groupMembers = $this->entityManager->getRepository(\App\Entity\Queue::class)
                        ->createQueryBuilder('q')
                        ->leftJoin('q.patient', 'p')
                        ->where('q.metadata LIKE :groupId')
                        ->setParameter('groupId', '%"groupId":"' . $groupId . '"%')
                        ->getQuery()
                        ->getResult();
                    $patients = array_map(function($q) {
                        $p = $q->getPatient();
                        return $p ? [
                            'id' => $p->getId(),
                            'name' => $p->getName(),
                            'displayName' => method_exists($p, 'getDisplayName') ? $p->getDisplayName() : $p->getName()
                        ] : null;
                    }, $groupMembers);
                    $patients = array_filter($patients); // Remove nulls

                    $groupedQueues[$groupId] = [
                        'id' => $queue->getId(),
                        'queueNumber' => $queue->getQueueNumber(),
                        'registrationNumber' => $queue->getRegistrationNumber(),
                        'isGroupConsultation' => true,
                        'groupId' => $groupId,
                        'patients' => array_values($patients),
                        'patientCount' => count($patients),
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
                        'amount' => $queue->getAmount()
                    ];
                    $queueData[] = $groupedQueues[$groupId];
                }
            } else {
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
                    'amount' => $queue->getAmount()
                ];
            }
        }

        return $queueData;
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
            $queueDateTime = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
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
            $myt = new \DateTimeZone('Asia/Kuala_Lumpur');
            $queueDateTime = isset($data['queueDateTime']) 
                ? new \DateTimeImmutable($data['queueDateTime'], $myt)
                : new \DateTimeImmutable('now', $myt);
            
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
            
            // Clear relevant caches
            if ($this->cache) {
                $this->cache->delete('queue_list_' . date('Y-m-d') . '_all_page_1_limit_50');
                $this->cache->delete('queue_stats');
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
            
            // Update queue payment status
            $queue->setIsPaid(true);
            $queue->setPaidAt(new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kuala_Lumpur')));
            $queue->setPaymentMethod($data['paymentMethod']);
            $queue->setAmount($data['amount']);
            
            // If status is 'completed_consultation', change it to 'completed'
            if ($queue->getStatus() === 'completed_consultation') {
                $queue->setStatus('completed');
            }
            
            $this->entityManager->flush();
            
            // Clear relevant caches
            if ($this->cache) {
                $this->cache->delete('queue_list_' . date('Y-m-d') . '_all_page_1_limit_50');
                $this->cache->delete('queue_stats');
            }
            
            $this->logger->info('Queue payment processed', [
                'queueId' => $id,
                'paymentMethod' => $data['paymentMethod'],
                'amount' => $data['amount']
            ]);
            
            return new JsonResponse([
                'message' => 'Payment processed successfully',
                'isPaid' => $queue->getIsPaid(),
                'paidAt' => $queue->getPaidAt()->format('Y-m-d H:i:s'),
                'status' => $queue->getStatus()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error processing queue payment: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to process payment'], 500);
        }
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
        $today = new \DateTime('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
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


}
