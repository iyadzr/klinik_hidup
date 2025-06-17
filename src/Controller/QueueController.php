<?php

namespace App\Controller;

use App\Entity\Queue;
use App\Entity\Patient;
use App\Entity\Doctor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/queue')]
class QueueController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_queue_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $date = $request->query->get('date');
            if (!$date) {
                // Default to today in Asia/Kuala_Lumpur
                $dt = new \DateTime('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
                $date = $dt->format('Y-m-d');
            }
            
            // Create date range for the specified date
            $start = new \DateTime($date . ' 00:00:00', new \DateTimeZone('Asia/Kuala_Lumpur'));
            $end = new \DateTime($date . ' 23:59:59', new \DateTimeZone('Asia/Kuala_Lumpur'));
            
            // Query with better date handling and ordering
            $queues = $this->entityManager->getRepository(Queue::class)->createQueryBuilder('q')
                ->leftJoin('q.patient', 'p')
                ->leftJoin('q.doctor', 'd')
                ->where('q.queueDateTime BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->orderBy('q.registrationNumber', 'ASC') // Order by registration number for better queue order
                ->addOrderBy('q.queueDateTime', 'ASC')
                ->getQuery()->getResult();
        
        $queueData = [];
        $groupedQueues = []; // To track processed groups
        
        foreach ($queues as $queue) {
            $patient = $queue->getPatient();
            $doctor = $queue->getDoctor();
            
            if (!$patient || !$doctor) {
                continue; // Skip queues with missing patient or doctor
            }
            
            // Handle group consultations
            if ($queue->isGroupConsultation()) {
                $groupId = $queue->getGroupId();
                
                if (!isset($groupedQueues[$groupId])) {
                    // First patient in the group - create group entry
                    $metadata = $queue->getMetadataArray();
                    $groupMembers = $metadata['groupMembers'] ?? [];
                    
                    $groupedQueues[$groupId] = [
                        'id' => $queue->getId(),
                        'queueNumber' => $queue->getQueueNumber(),
                        'registrationNumber' => $queue->getRegistrationNumber(),
                        'isGroupConsultation' => true,
                        'groupId' => $groupId,
                        'mainPatient' => [
                            'id' => $patient->getId(),
                            'name' => $patient->getName(),
                            'displayName' => method_exists($patient, 'getDisplayName') ? $patient->getDisplayName() : $patient->getName()
                        ],
                        'groupMembers' => $groupMembers,
                        'totalPatients' => count($groupMembers),
                        'doctor' => [
                            'id' => $doctor->getId(),
                            'name' => $doctor->getName(),
                            'displayName' => method_exists($doctor, 'getDisplayName') ? $doctor->getDisplayName() : $doctor->getName()
                        ],
                        'status' => $queue->getStatus(),
                        'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s'),
                        'time' => $queue->getQueueDateTime()->format('d M Y, h:i:s a')
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
                    'time' => $queue->getQueueDateTime()->format('d M Y, h:i:s a')
                ];
            }
        }

        return new JsonResponse($queueData);
        
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to load queue data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'app_queue_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $patient = $this->entityManager->getRepository(Patient::class)->find($data['patientId']);
        $doctor = $this->entityManager->getRepository(Doctor::class)->find($data['doctorId']);

        if (!$patient || !$doctor) {
            return new JsonResponse(['error' => 'Patient or Doctor not found'], 404);
        }

        $queue = new Queue();
        $queue->setPatient($patient);
        $queue->setDoctor($doctor);
        $queue->setQueueDateTime(new \DateTimeImmutable());
        $queue->setStatus('waiting');
        
        // Assign queue number based on registration time and running number for the hour
        $queueDateTime = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
        $queue->setQueueDateTime($queueDateTime);
        $hour = (int)$queueDateTime->format('G'); // 0-23, e.g., 8, 9, 15
        
        // Find the latest registration number for today
        $qb = $this->entityManager->getRepository(Queue::class)->createQueryBuilder('q');
        $qb->select('q.registrationNumber')
            ->where('q.queueDateTime >= :startOfDay')
            ->andWhere('q.queueDateTime < :endOfDay')
            ->setParameter('startOfDay', $queueDateTime->format('Y-m-d 00:00:00'))
            ->setParameter('endOfDay', $queueDateTime->format('Y-m-d 23:59:59'))
            ->orderBy('q.registrationNumber', 'DESC')
            ->setMaxResults(1);
        $lastQueue = $qb->getQuery()->getOneOrNullResult();
        
        // Generate registration number starting with current hour
        $baseNumber = $hour * 100 + 1; // e.g., 1501 for 3pm
        $registrationNumber = $baseNumber;
        
        if ($lastQueue && isset($lastQueue['registrationNumber'])) {
            $lastRegNumber = (int)$lastQueue['registrationNumber'];
            
            // If we already have registrations today
            if ($lastRegNumber >= $baseNumber) {
                // Continue from the last number + 1
                $registrationNumber = $lastRegNumber + 1;
            } else {
                // Start fresh with the hour-based number
                $registrationNumber = $baseNumber;
            }
        }
        
        // Generate queue number (for display purposes, can be different from registration)
        $queueNumber = sprintf('%04d', $registrationNumber);
        $queue->setQueueNumber($queueNumber);
        $queue->setRegistrationNumber($registrationNumber);

        $this->entityManager->persist($queue);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $queue->getId(),
            'queueNumber' => $queue->getQueueNumber(),
            'registrationNumber' => $queue->getRegistrationNumber(),
            'message' => 'Queue created successfully'
        ], 201);
    }

    #[Route('/group', name: 'app_queue_create_group', methods: ['POST'])]
    public function createGroup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['patients']) || !is_array($data['patients']) || empty($data['patients'])) {
            return new JsonResponse(['error' => 'Patients array is required'], 400);
        }

        $doctor = $this->entityManager->getRepository(Doctor::class)->find($data['doctorId']);
        if (!$doctor) {
            return new JsonResponse(['error' => 'Doctor not found'], 404);
        }

        // Generate shared registration number for the group
        $queueDateTime = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
        $hour = (int)$queueDateTime->format('G');
        
        // Find the latest registration number for today
        $qb = $this->entityManager->getRepository(Queue::class)->createQueryBuilder('q');
        $qb->select('q.registrationNumber')
            ->where('q.queueDateTime >= :startOfDay')
            ->andWhere('q.queueDateTime < :endOfDay')
            ->setParameter('startOfDay', $queueDateTime->format('Y-m-d 00:00:00'))
            ->setParameter('endOfDay', $queueDateTime->format('Y-m-d 23:59:59'))
            ->orderBy('q.registrationNumber', 'DESC')
            ->setMaxResults(1);
        $lastQueue = $qb->getQuery()->getOneOrNullResult();
        
        // Generate registration number starting with current hour
        $baseNumber = $hour * 100 + 1; // e.g., 1501 for 3pm
        $registrationNumber = $baseNumber;
        
        if ($lastQueue && isset($lastQueue['registrationNumber'])) {
            $lastRegNumber = (int)$lastQueue['registrationNumber'];
            
            // If we already have registrations today
            if ($lastRegNumber >= $baseNumber) {
                // Continue from the last number + 1
                $registrationNumber = $lastRegNumber + 1;
            } else {
                // Start fresh with the hour-based number
                $registrationNumber = $baseNumber;
            }
        }
        
        $sharedQueueNumber = sprintf('%04d', $registrationNumber);

        $createdQueues = [];
        $groupId = uniqid('grp_'); // Generate unique group ID

        foreach ($data['patients'] as $patientData) {
            $patient = $this->entityManager->getRepository(Patient::class)->find($patientData['id']);
            if (!$patient) {
                continue; // Skip if patient not found
            }

            $queue = new Queue();
            $queue->setPatient($patient);
            $queue->setDoctor($doctor);
            $queue->setQueueDateTime($queueDateTime);
            $queue->setStatus('waiting');
            $queue->setQueueNumber($sharedQueueNumber); // Same queue number for all
            $queue->setRegistrationNumber($registrationNumber);
            
            // Add group consultation metadata
            $metadata = [
                'isGroupConsultation' => true,
                'groupId' => $groupId,
                'relationship' => $patientData['relationship'] ?? '',
                'groupMembers' => array_map(function($p) { 
                    return ['id' => $p['id'], 'name' => $p['name'], 'relationship' => $p['relationship'] ?? '']; 
                }, $data['patients'])
            ];
            
            if (method_exists($queue, 'setMetadata')) {
                $queue->setMetadata(json_encode($metadata));
            }

            $this->entityManager->persist($queue);
            
            $createdQueues[] = [
                'patientId' => $patient->getId(),
                'patientName' => $patient->getName(),
                'relationship' => $patientData['relationship'] ?? ''
            ];
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'queueNumber' => $sharedQueueNumber,
            'groupId' => $groupId,
            'patients' => $createdQueues,
            'message' => 'Group consultation queue created successfully'
        ], 201);
    }

    #[Route('/group/{groupId}', name: 'app_queue_get_group', methods: ['GET'])]
    public function getGroup(string $groupId): JsonResponse
    {
        $qb = $this->entityManager->getRepository(Queue::class)->createQueryBuilder('q');
        $qb->select('q')
            ->where('q.metadata LIKE :groupId')
            ->setParameter('groupId', '%"groupId":"' . $groupId . '"%')
            ->orderBy('q.queueDateTime', 'ASC')
            ->setMaxResults(1);
        
        $queue = $qb->getQuery()->getOneOrNullResult();
        
        if (!$queue) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        $metadata = $queue->getMetadataArray();
        
        return new JsonResponse([
            'id' => $queue->getId(),
            'queueNumber' => $queue->getQueueNumber(),
            'groupId' => $groupId,
            'metadata' => $metadata,
            'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s'),
            'status' => $queue->getStatus()
        ]);
    }

    #[Route('/{id}/status', name: 'app_queue_update_status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $queue = $this->entityManager->getRepository(Queue::class)->find($id);

        if (!$queue) {
            return new JsonResponse(['error' => 'Queue not found'], 404);
        }

        $queue->setStatus($data['status']);
        $this->entityManager->flush();

        // Broadcast queue update for SSE
        $this->broadcastQueueUpdate($queue);

        return new JsonResponse(['message' => 'Queue status updated successfully']);
    }

    private function broadcastQueueUpdate($queue): void
    {
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
        $tempDir = sys_get_temp_dir();
        $updateFile = $tempDir . '/queue_updates.json';
        $updates = [];
        if (file_exists($updateFile)) {
            $content = file_get_contents($updateFile);
            $updates = json_decode($content, true) ?: [];
        }
        $updates[] = $updateData;
        file_put_contents($updateFile, json_encode($updates));
    }

    #[Route('/debug', name: 'app_queue_debug', methods: ['GET'])]
    public function debug(): JsonResponse
    {
        // Get today's date in MYT
        $today = new \DateTime('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
        $todayStr = $today->format('Y-m-d');
        
        // Count total queues
        $totalQueues = $this->entityManager->getRepository(Queue::class)->count([]);
        
        // Count today's queues
        $start = new \DateTime($todayStr . ' 00:00:00', new \DateTimeZone('Asia/Kuala_Lumpur'));
        $end = new \DateTime($todayStr . ' 23:59:59', new \DateTimeZone('Asia/Kuala_Lumpur'));
        
        $todayQueues = $this->entityManager->getRepository(Queue::class)
            ->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.queueDateTime BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
            
        // Get all today's queues with details
        $todayQueueDetails = $this->entityManager->getRepository(Queue::class)
            ->createQueryBuilder('q')
            ->select('q.id, q.queueDateTime, q.status, q.queueNumber, q.registrationNumber, p.name as patientName, d.name as doctorName')
            ->join('q.patient', 'p')
            ->join('q.doctor', 'd')
            ->where('q.queueDateTime BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('q.registrationNumber', 'ASC')
            ->getQuery()
            ->getResult();
            
        // Count by status
        $statusCounts = $this->entityManager->getRepository(Queue::class)
            ->createQueryBuilder('q')
            ->select('q.status, COUNT(q.id) as count')
            ->where('q.queueDateTime BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('q.status')
            ->getQuery()
            ->getResult();
        
        return new JsonResponse([
            'currentTime' => $today->format('Y-m-d H:i:s T'),
            'todayDate' => $todayStr,
            'dateRange' => [
                'start' => $start->format('Y-m-d H:i:s T'),
                'end' => $end->format('Y-m-d H:i:s T')
            ],
            'queues' => [
                'total' => $totalQueues,
                'today' => $todayQueues,
                'todayDetails' => $todayQueueDetails,
                'statusCounts' => $statusCounts
            ]
        ]);
    }
}
