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
            
            // Set consultation date to now if not provided
            $consultation->setConsultationDate(new \DateTime());
            
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
            
            return new JsonResponse([
                'id' => $consultation->getId(),
                'message' => 'Consultation created successfully'
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
            $history[] = [
                'id' => $consultation->getId(),
                'consultationDate' => $consultation->getConsultationDate()->format('Y-m-d\TH:i'),
                'diagnosis' => $consultation->getDiagnosis(),
                'medications' => $consultation->getMedications(),
                'notes' => $consultation->getNotes(),
                'followUpPlan' => $consultation->getFollowUpPlan(),
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
                'consultationFee' => $consultation->getConsultationFee(),
                'medicinesFee' => $consultation->getMedicinesFee(),
                'totalAmount' => $consultation->getTotalAmount(),
                'isPaid' => $consultation->getIsPaid(),
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
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $consultation = $entityManager->getRepository(Consultation::class)->find($id);
            
            if (!$consultation) {
                return new JsonResponse(['message' => 'Consultation not found'], 404);
            }
            
            if ($consultation->getIsPaid()) {
                return new JsonResponse(['message' => 'Consultation is already paid'], 400);
            }
            
            $consultation->setIsPaid(true);
            $consultation->setPaidAt(new \DateTime());
            
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
