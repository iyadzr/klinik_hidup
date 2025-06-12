<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\Patient;
use App\Entity\Doctor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/consultations')]
class ConsultationController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
    public function create(Request $request, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $logger->info('Received consultation data', ['data' => $data]);
            
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
            $consultation->setConsultationDate(new \DateTime('now', $myt));
            
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
            
            // Update queue status to 'completed' for this patient/doctor combination
            $queueRepository = $this->entityManager->getRepository(\App\Entity\Queue::class);
            $queue = $queueRepository->findOneBy([
                'patient' => $patient,
                'doctor' => $doctor,
                'status' => 'in_consultation'
            ]);
            
            if ($queue) {
                $queue->setStatus('completed');
                $this->entityManager->flush();
                
                // Trigger real-time update
                $this->broadcastQueueUpdate($queue);
            }
            
            return new JsonResponse([
                'id' => $consultation->getId(),
                'message' => 'Consultation created successfully',
                'queueUpdated' => $queue ? true : false
            ], 201);
        } catch (\Exception $e) {
            $logger->error('Error creating consultation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => 'Error creating consultation: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/patient/{id}', name: 'app_consultation_history', methods: ['GET'])]
    public function getPatientHistory(int $id): JsonResponse
    {
        $consultations = $this->entityManager->getRepository(Consultation::class)
            ->findBy(['patient' => $id], ['consultationDate' => 'DESC']);

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
                ]
            ];
        }

        return new JsonResponse($history);
    }

    #[Route('', name: 'app_consultations_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $consultations = $entityManager->getRepository(Consultation::class)->findAll();
        $data = [];

        foreach ($consultations as $consultation) {
            $patient = $consultation->getPatient();
            $doctor = $consultation->getDoctor();

            // Get prescribed medications
            $prescribedMedications = $entityManager->getRepository(\App\Entity\PrescribedMedication::class)
                ->findBy(['consultation' => $consultation]);

            $medicationsArray = [];
            foreach ($prescribedMedications as $prescribedMed) {
                $medicationsArray[] = [
                    'name' => $prescribedMed->getMedication()->getName(),
                    'quantity' => $prescribedMed->getQuantity(),
                    'unitType' => $prescribedMed->getMedication()->getUnitType(),
                    'instructions' => $prescribedMed->getInstructions()
                ];
            }

            $data[] = [
                'id' => $consultation->getId(),
                'patientId' => $patient->getId(),
                'patientName' => $patient->getName(),
                'doctorId' => $doctor->getId(),
                'doctorName' => $doctor->getName(),
                'createdAt' => $consultation->getCreatedAt()->format('Y-m-d H:i:s'),
                'symptoms' => $consultation->getSymptoms(),
                'diagnosis' => $consultation->getDiagnosis(),
                'treatment' => $consultation->getTreatment(),
                'notes' => $consultation->getNotes(), // Remarks field
                'remarks' => $consultation->getNotes(), // Alternative field name
                'medications' => $consultation->getMedications(), // Legacy text field
                'prescribedMedications' => $medicationsArray, // Structured medications
                'consultationFee' => $consultation->getConsultationFee(),
                'medicinesFee' => $consultation->getMedicinesFee(),
                'totalAmount' => $consultation->getTotalAmount(),
                'isPaid' => $consultation->getIsPaid(),
                'status' => $consultation->getIsPaid() ? 'Paid' : 'Unpaid',
                'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'app_consultations_get', methods: ['GET'])]
    public function get(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $consultation = $entityManager->getRepository(Consultation::class)->find($id);
        
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
            'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null
        ]);
    }

    #[Route('/{id}/payment', name: 'app_consultation_payment', methods: ['POST'])]
    public function updatePaymentStatus(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $consultation = $entityManager->getRepository(Consultation::class)->find($id);
            
            if (!$consultation) {
                return new JsonResponse(['message' => 'Consultation not found'], 404);
            }
            
            if ($consultation->getIsPaid()) {
                return new JsonResponse(['message' => 'Consultation is already paid'], 400);
            }
            
            $consultation->setIsPaid(true);
            
            // Set payment date in MYT timezone
            $myt = new \DateTimeZone('Asia/Kuala_Lumpur');
            $consultation->setPaidAt(new \DateTime('now', $myt));
            
            // Store payment method if provided
            if (isset($data['paymentMethod'])) {
                // You might want to add a paymentMethod field to the Consultation entity
                // For now, we'll just log it
                $logger->info('Payment processed', [
                    'consultation_id' => $id,
                    'payment_method' => $data['paymentMethod'],
                    'amount' => $consultation->getTotalAmount()
                ]);
            }
            
            $entityManager->flush();
            
            return new JsonResponse([
                'message' => 'Payment processed successfully',
                'consultation' => [
                    'id' => $consultation->getId(),
                    'isPaid' => $consultation->getIsPaid(),
                    'paidAt' => $consultation->getPaidAt()->format('Y-m-d H:i:s'),
                    'totalAmount' => $consultation->getTotalAmount()
                ]
            ]);
            
        } catch (\Exception $e) {
            $logger->error('Error processing payment: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Error processing payment'], 500);
        }
    }
}
