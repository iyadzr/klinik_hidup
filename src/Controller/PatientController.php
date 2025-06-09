<?php

namespace App\Controller;

use App\Entity\Patient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/patients')]
class PatientController extends AbstractController
{
    #[Route('/registration/{registrationNumber}', name: 'app_patient_by_registration', methods: ['GET'])]
    public function getByRegistrationNumber(int $registrationNumber, EntityManagerInterface $entityManager): JsonResponse
    {
        $queue = $entityManager->getRepository(\App\Entity\Queue::class)->findOneBy(['registrationNumber' => $registrationNumber]);
        if (!$queue) {
            return $this->json(['error' => 'Registration number not found'], 404);
        }
        $patient = $queue->getPatient();
        if (!$patient) {
            return $this->json(['error' => 'Patient not found'], 404);
        }
        return $this->json([
            'id' => $patient->getId(),
            'name' => $patient->getName(),
            'nric' => $patient->getNric(),  
            'email' => $patient->getEmail(),
            'phone' => $patient->getPhone(),
            'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
            'medicalHistory' => $patient->getMedicalHistory(),
            'company' => method_exists($patient, 'getCompany') ? $patient->getCompany() : null,
            'preInformedIllness' => method_exists($patient, 'getPreInformedIllness') ? $patient->getPreInformedIllness() : null,
            'displayName' => $patient->getName(),
        ]);
    }

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('', name: 'app_patient_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $patients = $entityManager->getRepository(Patient::class)->findAll();
        $result = array_map(function($patient) {
            return [
                'id' => $patient->getId(),
                'name' => $patient->getName(),
                'nric' => $patient->getNric(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'dateOfBirth' => $patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : null,
                'medicalHistory' => $patient->getMedicalHistory(),
                'company' => method_exists($patient, 'getCompany') ? $patient->getCompany() : null,
                'preInformedIllness' => method_exists($patient, 'getPreInformedIllness') ? $patient->getPreInformedIllness() : null,

                'displayName' => $patient->getName(),
            ];
        }, $patients);
        return $this->json($result);
    }

    #[Route('/count', name: 'app_patient_count', methods: ['GET'])]
    public function count(EntityManagerInterface $entityManager): JsonResponse
    {
        $repository = $entityManager->getRepository(Patient::class);
        $count = $repository->count([]);
        
        $this->logger->info('Patient count requested', [
            'count' => $count
        ]);
        
        return $this->json(['count' => $count]);
    }

    #[Route('/search', name: 'app_patient_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $query = $request->query->get('query', '');
        
        if (empty($query)) {
            return $this->json([]);
        }
        
        $qb = $entityManager->createQueryBuilder();
        $qb->select('p')
           ->from(Patient::class, 'p')
           ->where('p.name LIKE :query')
           ->orWhere('p.nric LIKE :query')
           ->orWhere('p.phone LIKE :query')
           ->setParameter('query', '%' . $query . '%')
           ->setMaxResults(10);
        
        $patients = $qb->getQuery()->getResult();
        
        // Also search for registration numbers in the Queue entity
        if (is_numeric($query)) {
            $queueQb = $entityManager->createQueryBuilder();
            $queueQb->select('q')
                   ->from('App\\Entity\\Queue', 'q')
                   ->where('q.registrationNumber = :regNum')
                   ->setParameter('regNum', (int)$query);
            
            $queues = $queueQb->getQuery()->getResult();
            
            foreach($queues as $queue) {
                $patient = $queue->getPatient();
                if ($patient) {
                    // Check if this patient is already in our results
                    $found = false;
                    foreach ($patients as $p) {
                        if ($p->getId() === $patient->getId()) {
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $patients[] = $patient;
                    }
                }
            }
        }
        
        // Format the patient data for the response
        $result = array_map(function($patient) use ($entityManager) {
            // Try to find registration number
            $queue = $entityManager->getRepository('App\\Entity\\Queue')->findOneBy(['patient' => $patient]);
            $registrationNumber = $queue ? $queue->getRegistrationNumber() : null;
            
            return [
                'id' => $patient->getId(),
                'name' => $patient->getName(),
                'nric' => $patient->getNric(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'dateOfBirth' => $patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : null,
                'gender' => method_exists($patient, 'getGender') ? $patient->getGender() : null,
                'address' => method_exists($patient, 'getAddress') ? $patient->getAddress() : null,
                'registrationNumber' => $registrationNumber
            ];
        }, $patients);
        
        return $this->json($result);
    }


    #[Route('', name: 'app_patient_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $patient = new Patient();
        $patient->setName($data['name']);
        $patient->setNric($data['nric']);
        $patient->setEmail($data['email']);
        $patient->setPhone($data['phone']);
        if (isset($data['dateOfBirth'])) {
            $patient->setDateOfBirth(new \DateTime($data['dateOfBirth']));
        }
        if (isset($data['medicalHistory'])) {
            $patient->setMedicalHistory($data['medicalHistory']);
        }

        $entityManager->persist($patient);
        $entityManager->flush();

        $this->logger->info('Patient created', [
            'id' => $patient->getId(),
            'email' => $patient->getEmail(),
        ]);

        return $this->json([
            'message' => 'Patient created successfully',
            'patient' => [
                'id' => $patient->getId(),
                'name' => $patient->getName(),
                'nric' => $patient->getNric(),
                'email' => $patient->getEmail(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_patient_show', methods: ['GET'])]
    public function show(Patient $patient): JsonResponse
    {
        return $this->json([
            'id' => $patient->getId(),
            'name' => $patient->getName(),
            'nric' => $patient->getNric(),
            'email' => $patient->getEmail(),
            'phone' => $patient->getPhone(),
            'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
            'medicalHistory' => $patient->getMedicalHistory(),
            'company' => $patient->getCompany(),
            'preInformedIllness' => $patient->getPreInformedIllness(),
            'displayName' => $patient->getName(),
        ]);
    }

    #[Route('/{id}', name: 'app_patient_update', methods: ['PUT'])]
    public function update(Request $request, Patient $patient, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $patient->setName($data['name']);
        }
        if (isset($data['nric'])) {
            $patient->setNric($data['nric']);
        }
        if (isset($data['email'])) {
            $patient->setEmail($data['email']);
        }
        if (isset($data['phone'])) {
            $patient->setPhone($data['phone']);
        }
        if (isset($data['dateOfBirth'])) {
            $patient->setDateOfBirth(new \DateTime($data['dateOfBirth']));
        }
        if (isset($data['medicalHistory'])) {
            $patient->setMedicalHistory($data['medicalHistory']);
        }

        $entityManager->flush();

        $this->logger->info('Patient updated', [
            'id' => $patient->getId(),
            'email' => $patient->getEmail()
        ]);

        return $this->json([
            'message' => 'Patient updated successfully',
            'patient' => [
                'id' => $patient->getId(),
                'name' => $patient->getName(),
                'nric' => $patient->getNric(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'dateOfBirth' => $patient->getDateOfBirth()?->format('Y-m-d'),
                'medicalHistory' => $patient->getMedicalHistory(),
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_patient_delete', methods: ['DELETE'])]
    public function delete(Patient $patient, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($patient);
        $entityManager->flush();

        $this->logger->info('Patient deleted', [
            'id' => $patient->getId()
        ]);

        return $this->json(['message' => 'Patient deleted successfully']);
    }
}
