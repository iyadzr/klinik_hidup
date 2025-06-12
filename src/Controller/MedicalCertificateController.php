<?php

namespace App\Controller;

use App\Entity\MedicalCertificate;
use App\Entity\Patient;
use App\Entity\Doctor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/medical-certificates')]
class MedicalCertificateController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/next-number', name: 'app_medical_certificate_next_number', methods: ['GET'])]
    public function getNextNumber(): JsonResponse
    {
        try {
            // Get the next MC ID by finding the highest current ID and adding 1
            $lastMC = $this->entityManager
                ->getRepository(MedicalCertificate::class)
                ->createQueryBuilder('mc')
                ->select('MAX(mc.id) as maxId')
                ->getQuery()
                ->getSingleScalarResult();
            
            $nextNumber = ($lastMC ?? 0) + 1;
            
            // Format as 6-digit running number (e.g., 426741, 426742, etc.)
            $runningNumber = str_pad($nextNumber + 426740, 6, '0', STR_PAD_LEFT);
            
            return new JsonResponse([
                'runningNumber' => $runningNumber
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'runningNumber' => '426741' // Fallback number
            ]);
        }
    }

    #[Route('', name: 'app_medical_certificate_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $patient = $this->entityManager->getRepository(Patient::class)->find($data['patientId']);
        $doctor = $this->entityManager->getRepository(Doctor::class)->find($data['doctorId']);

        if (!$patient || !$doctor) {
            return new JsonResponse(['error' => 'Patient or Doctor not found'], 404);
        }

        $certificate = new MedicalCertificate();
        $certificate->setPatient($patient);
        $certificate->setDoctor($doctor);
        $certificate->setIssueDate(new \DateTimeImmutable());
        $certificate->setStartDate(new \DateTimeImmutable($data['startDate']));
        $certificate->setEndDate(new \DateTimeImmutable($data['endDate']));
        $certificate->setDiagnosis($data['diagnosis']);
        $certificate->setRemarks($data['remarks'] ?? null);

        // Generate certificate number
        $certificateNumber = $this->entityManager
            ->getRepository(MedicalCertificate::class)
            ->generateCertificateNumber();
        $certificate->setCertificateNumber($certificateNumber);

        $this->entityManager->persist($certificate);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $certificate->getId(),
            'certificateNumber' => $certificate->getCertificateNumber(),
            'message' => 'Medical certificate created successfully'
        ], 201);
    }

    #[Route('/{id}', name: 'app_medical_certificate_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $certificate = $this->entityManager->getRepository(MedicalCertificate::class)->find($id);

        if (!$certificate) {
            return new JsonResponse(['error' => 'Medical certificate not found'], 404);
        }

        return new JsonResponse([
            'id' => $certificate->getId(),
            'certificateNumber' => $certificate->getCertificateNumber(),
            'patient' => [
                'id' => $certificate->getPatient()->getId(),
                'name' => $certificate->getPatient()->getName()
            ],
            'doctor' => [
                'id' => $certificate->getDoctor()->getId(),
                'name' => $certificate->getDoctor()->getName()
            ],
            'issueDate' => $certificate->getIssueDate()->format('Y-m-d H:i:s'),
            'startDate' => $certificate->getStartDate()->format('Y-m-d'),
            'endDate' => $certificate->getEndDate()->format('Y-m-d'),
            'diagnosis' => $certificate->getDiagnosis(),
            'remarks' => $certificate->getRemarks()
        ]);
    }

    #[Route('/patient/{patientId}', name: 'app_medical_certificate_by_patient', methods: ['GET'])]
    public function getByPatient(int $patientId): JsonResponse
    {
        $certificates = $this->entityManager->getRepository(MedicalCertificate::class)
            ->findBy(['patient' => $patientId], ['issueDate' => 'DESC']);

        $certificateData = [];
        foreach ($certificates as $certificate) {
            $certificateData[] = [
                'id' => $certificate->getId(),
                'certificateNumber' => $certificate->getCertificateNumber(),
                'doctor' => [
                    'id' => $certificate->getDoctor()->getId(),
                    'name' => $certificate->getDoctor()->getName()
                ],
                'issueDate' => $certificate->getIssueDate()->format('Y-m-d H:i:s'),
                'startDate' => $certificate->getStartDate()->format('Y-m-d'),
                'endDate' => $certificate->getEndDate()->format('Y-m-d'),
                'diagnosis' => $certificate->getDiagnosis()
            ];
        }

        return new JsonResponse($certificateData);
    }
}
