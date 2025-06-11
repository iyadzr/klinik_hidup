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
        foreach ($queues as $queue) {
            $patient = $queue->getPatient();
            $doctor = $queue->getDoctor();
            
            if (!$patient || !$doctor) {
                continue; // Skip queues with missing patient or doctor
            }
            
            $queueData[] = [
                'id' => $queue->getId(),
                'queueNumber' => $queue->getQueueNumber(),
                'registrationNumber' => $queue->getRegistrationNumber(),
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
