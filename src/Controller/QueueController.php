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
    public function list(): JsonResponse
    {
        $queues = $this->entityManager->getRepository(Queue::class)->findTodayQueue();
        
        $queueData = [];
        foreach ($queues as $queue) {
            $queueData[] = [
                'id' => $queue->getId(),
                'queueNumber' => $queue->getQueueNumber(),
                'patient' => [
                    'id' => $queue->getPatient()->getId(),
                    'name' => $queue->getPatient()->getName()
                ],
                'doctor' => [
                    'id' => $queue->getDoctor()->getId(),
                    'name' => $queue->getDoctor()->getName()
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
        
        // Get next queue number
        $nextQueueNumber = $this->entityManager->getRepository(Queue::class)->findNextQueueNumber();
        $queue->setQueueNumber($nextQueueNumber);

        $this->entityManager->persist($queue);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $queue->getId(),
            'queueNumber' => $queue->getQueueNumber(),
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
