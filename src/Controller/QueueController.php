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
        $date = $request->query->get('date', date('Y-m-d'));
        $queues = $this->entityManager->getRepository(Queue::class)->findByDate($date);
        
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
                        'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s')
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
                    'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s')
                ];
            }
        }

        return new JsonResponse($queueData);
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

        // Generate shared queue number for the group
        $queueDateTime = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kuala_Lumpur'));
        $hour = (int)$queueDateTime->format('G');
        
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
        $sharedQueueNumber = sprintf('%d%02d', $hour, $runningNumber);

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
            $queue->setRegistrationNumber((int)$sharedQueueNumber);
            
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

        return new JsonResponse(['message' => 'Queue status updated successfully']);
    }
}
