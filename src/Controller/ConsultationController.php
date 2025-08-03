<?php

namespace App\Controller;

use App\Entity\Consultation;
use App\Entity\Patient;
use App\Entity\Doctor;
use App\Entity\Queue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/consultations')]
class ConsultationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ?CacheInterface $cache;
    
    // Rate limiting storage
    private static array $requestCounts = [];
    private static int $maxRequestsPerMinute = 30;

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
                'queueDateTime' => $queue->getQueueDateTime()->format('Y-m-d H:i:s'),
                'isGroupConsultation' => $queue->isGroupConsultation(),
                'groupId' => $queue->getGroupId()
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
    public function create(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $data = json_decode($request->getContent(), true);
            $this->logger->info('Received consultation data', [
                'totalAmount' => $data['totalAmount'] ?? 'NOT_SET',
                'consultationFee' => $data['consultationFee'] ?? 'NOT_SET',
                'allFields' => array_keys($data)
            ]);
            
            // Log the specific status value being sent
            if (isset($data['status'])) {
                $this->logger->info('Status value received', [
                    'status' => $data['status'],
                    'length' => strlen($data['status'])
                ]);
            }
            
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
            
            // Link to queue if queueId is provided
            $queue = null;
            if (!empty($data['queueId'])) {
                $queue = $this->entityManager->getRepository(Queue::class)->find($data['queueId']);
                if ($queue) {
                    $queue->setConsultation($consultation);
                    $this->logger->info('Linked consultation to queue', [
                        'consultationId' => 'will_be_set_after_persist',
                        'queueId' => $queue->getId(),
                        'queueNumber' => $queue->getQueueNumber()
                    ]);
                }
            }
            
            // Set consultation date to now in Malaysia timezone
            $now = \App\Service\TimezoneService::now();
            $consultation->setConsultationDate($now);
            
            // Ensure createdAt is also set properly (constructor should handle this, but let's be explicit)
            if (!$consultation->getCreatedAt()) {
                $consultation->setCreatedAt($now);
            }
            
            // Set all fields from the request data
            if (isset($data['symptoms'])) $consultation->setSymptoms($data['symptoms']);
            if (isset($data['diagnosis'])) $consultation->setDiagnosis($data['diagnosis']);
            if (isset($data['treatment'])) $consultation->setTreatment($data['treatment']);
            if (isset($data['medications'])) $consultation->setMedications($data['medications']);
            if (isset($data['notes'])) $consultation->setNotes($data['notes']);
            
            // Handle status field explicitly
            if (isset($data['status'])) {
                $this->logger->info('Setting status from frontend', [
                    'status' => $data['status'],
                    'length' => strlen($data['status'])
                ]);
                $consultation->setStatus($data['status']);
            }
            
            // Handle payment information
            if (isset($data['totalAmount'])) $consultation->setTotalAmount($data['totalAmount']);
            
            // Handle medical certificate information
            if (isset($data['hasMedicalCertificate']) && $data['hasMedicalCertificate']) {
                $consultation->setHasMedicalCertificate(true);
                if (isset($data['mcStartDate'])) $consultation->setMcStartDate(new \DateTime($data['mcStartDate']));
                if (isset($data['mcEndDate'])) $consultation->setMcEndDate(new \DateTime($data['mcEndDate']));
                if (isset($data['mcNumber'])) $consultation->setMcNumber($data['mcNumber']);
                
                // Auto-generate MC running number if not provided
                if (isset($data['mcRunningNumber']) && !empty($data['mcRunningNumber'])) {
                    $consultation->setMcRunningNumber($data['mcRunningNumber']);
                } else {
                    // Generate new MC running number using counter
                    $receiptCounterRepo = $this->entityManager->getRepository(\App\Entity\ReceiptCounter::class);
                    $nextMCNumber = $receiptCounterRepo->getNextMCNumber();
                    $consultation->setMcRunningNumber((string)$nextMCNumber);
                    
                    $this->logger->info('Generated MC running number', [
                        'mcRunningNumber' => $nextMCNumber,
                        'consultationId' => $consultation->getId()
                    ]);
                }
            }
            
            try {
                $this->logger->info('About to persist consultation', [
                    'status' => $consultation->getStatus(),
                    'status_length' => $consultation->getStatus() ? strlen($consultation->getStatus()) : 0
                ]);
                $this->entityManager->persist($consultation);
                
                // Persist queue if it was linked
                if ($queue) {
                    $this->entityManager->persist($queue);
                }
                
                $this->entityManager->flush();
                $this->logger->info('Consultation persisted successfully');
                
                // Do NOT create Payment record automatically - only create when payment is actually processed
                // Payment records should only be created when the clinic assistant processes the payment
                $this->logger->info('Consultation completed with total amount', [
                    'totalAmount' => $data['totalAmount'] ?? 0,
                    'consultationId' => $consultation->getId(),
                    'note' => 'Payment record will be created when payment is processed'
                ]);
            } catch (\Exception $persistError) {
                $this->logger->error('Error persisting consultation', [
                    'error' => $persistError->getMessage(),
                    'status' => $consultation->getStatus(),
                    'status_length' => $consultation->getStatus() ? strlen($consultation->getStatus()) : 0
                ]);
                throw $persistError;
            }
            
            // Handle prescribed medications if provided
            if (isset($data['prescribedMedications']) && is_array($data['prescribedMedications'])) {
                foreach ($data['prescribedMedications'] as $medData) {
                    if (!empty($medData['name']) && !empty($medData['quantity'])) {
                        
                        // Log medication info for debugging (medication fields are optional)
                        $this->logger->info('Processing prescribed medication', [
                            'medication' => $medData['name'],
                            'dosage' => $medData['dosage'] ?? 'not provided',
                            'frequency' => $medData['frequency'] ?? 'not provided',
                            'duration' => $medData['duration'] ?? 'not provided',
                            'quantity' => $medData['quantity'] ?? 'not provided'
                        ]);
                        
                        // Find or create medication
                        $medication = null;
                        if (!empty($medData['medicationId'])) {
                            $medication = $this->entityManager->getRepository(\App\Entity\Medication::class)->find($medData['medicationId']);
                        }
                        
                        // If medication not found by ID, try to find by name
                        if (!$medication && !empty($medData['name'])) {
                            $medication = $this->entityManager->getRepository(\App\Entity\Medication::class)->findOneBy(['name' => $medData['name']]);
                        }
                        
                        // Create new medication if it doesn't exist
                        if (!$medication) {
                            $medication = new \App\Entity\Medication();
                            $medication->setName($medData['name']);
                            $medication->setUnitType($medData['unitType'] ?? 'pieces');
                            $medication->setUnitDescription($medData['unitDescription'] ?? null);
                            $medication->setCategory($medData['category'] ?? null);
                            $this->entityManager->persist($medication);
                            $this->entityManager->flush(); // Flush to get the ID
                        }
                        
                        // Create prescribed medication record
                        $prescribedMed = new \App\Entity\PrescribedMedication();
                        $prescribedMed->setConsultation($consultation);
                        $prescribedMed->setMedication($medication);
                        
                        if (isset($medData['quantity'])) {
                            $prescribedMed->setQuantity((int)$medData['quantity']);
                            $prescribedMed->setInstructions($medData['instructions'] ?? null);
                            
                            // Set dosage, frequency, and duration fields (optional, allow empty values)
                            $prescribedMed->setDosage(!empty($medData['dosage']) ? trim($medData['dosage']) : null);
                            $prescribedMed->setFrequency(!empty($medData['frequency']) ? trim($medData['frequency']) : null);
                            $prescribedMed->setDuration(!empty($medData['duration']) ? trim($medData['duration']) : null);
                            
                            // Handle actual price - allow 0 prices, use medication's selling price as fallback
                            if (isset($medData['actualPrice']) && is_numeric($medData['actualPrice'])) {
                                $prescribedMed->setActualPrice((string)$medData['actualPrice']);
                            } elseif ($medication && $medication->getSellingPrice()) {
                                // Fallback to medication's selling price if not provided
                                $prescribedMed->setActualPrice($medication->getSellingPrice());
                                $this->logger->info('Using medication selling price as fallback', [
                                    'medication' => $medication->getName(),
                                    'sellingPrice' => $medication->getSellingPrice()
                                ]);
                            } else {
                                // Set to null if no price available
                                $prescribedMed->setActualPrice(null);
                                $this->logger->warning('No price available for prescribed medication', [
                                    'medication' => $medication ? $medication->getName() : $medData['name']
                                ]);
                            }
                            
                            $this->entityManager->persist($prescribedMed);
                            
                            $this->logger->info('Persisted prescribed medication', [
                                'consultationId' => $consultation->getId(),
                                'medicationName' => $medData['name'],
                                'quantity' => $medData['quantity'],
                                'dosage' => $medData['dosage'] ?? null,
                                'frequency' => $medData['frequency'] ?? null,
                                'duration' => $medData['duration'] ?? null
                            ]);
                        }
                    }
                }
                $this->entityManager->flush();
                $this->logger->info('Flushed prescribed medications to database', [
                    'consultationId' => $consultation->getId()
                ]);
            }
            
            // Update queue status to 'completed_consultation' for this patient/doctor combination
            $queueRepository = $this->entityManager->getRepository(\App\Entity\Queue::class);
            $queue = $queueRepository->findOneBy([
                'patient' => $patient,
                'doctor' => $doctor,
                'status' => 'in_consultation'
            ]);
            
            if ($queue) {
                $this->logger->info('Updating queue status after consultation completion', [
                    'queueId' => $queue->getId(),
                    'queueNumber' => $queue->getQueueNumber(),
                    'previousStatus' => $queue->getStatus()
                ]);
                
                $queue->setStatus('completed_consultation');
                
                // ✅ TRANSFER CONSULTATION TOTAL AMOUNT TO QUEUE FOR PAYMENT MODAL
                if (isset($data['totalAmount']) && $data['totalAmount'] > 0) {
                    $queue->setAmount((string)$data['totalAmount']);
                    $this->logger->info('Updated queue with consultation total amount', [
                        'queueId' => $queue->getId(),
                        'totalAmount' => $data['totalAmount']
                    ]);
                }
                
                // Also update the consultation status
                $this->logger->info('Setting consultation status to completed_consultation');
                $consultation->setStatus('completed_consultation');
                
                // Payment record will be created later when payment is actually processed
                $this->logger->info('Queue updated with amount for future payment processing', [
                    'queueId' => $queue->getId(),
                    'queueNumber' => $queue->getQueueNumber(),
                    'totalAmount' => $data['totalAmount'] ?? 0
                ]);
                
                $this->entityManager->flush();
                
                $this->logger->info('Queue status updated successfully', [
                    'queueId' => $queue->getId(),
                    'newStatus' => $queue->getStatus()
                ]);
                
                // Trigger real-time update
                $this->broadcastQueueUpdate($queue);
            } else {
                $this->logger->warning('No queue found for patient/doctor combination', [
                    'patientId' => $patient->getId(),
                    'doctorId' => $doctor->getId()
                ]);
            }
            
            // Clear relevant caches aggressively
            if ($this->cache) {
                $today = date('Y-m-d');
                $cacheKeys = [
                    'consultations_list_' . $today,
                    'consultations_ongoing',
                    'consultations_ongoing_doctor_' . $doctor->getId(), // Doctor-specific ongoing cache
                    'consultations_today_all',
                    'consultations_today_all_doctor_' . $doctor->getId(), // Doctor-specific today cache
                    'payments_list_' . $today,
                    // Clear queue caches for immediate updates
                    'queue_list_' . $today . '_all_page_1_limit_50',
                    'queue_list_' . $today . '_waiting_page_1_limit_50',
                    'queue_list_' . $today . '_in_consultation_page_1_limit_50',
                    'queue_list_' . $today . '_completed_page_1_limit_50',
                    'queue_stats'
                ];
                
                foreach ($cacheKeys as $key) {
                    $this->cache->delete($key);
                    $this->logger->debug('Cleared cache key: ' . $key);
                }
                
                $this->logger->info('Cache cleared after consultation completion', [
                    'doctorId' => $doctor->getId(),
                    'clearedKeys' => count($cacheKeys)
                ]);
            }
            
            return new JsonResponse([
                'id' => $consultation->getId(),
                'message' => 'Consultation created successfully',
                'queueUpdated' => $queue ? true : false
            ], 201);
        } catch (\Exception $e) {
            $this->logger->error('Error creating consultation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $data,
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            // More specific error message for debugging
            $errorMessage = 'Error creating consultation: ' . $e->getMessage();
            if (strpos($e->getMessage(), 'prescribed_medication') !== false) {
                $errorMessage .= ' (Issue with prescribed medications)';
            }
            
            return new JsonResponse([
                'error' => $errorMessage,
                'details' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    #[Route('/patient/{id}', name: 'app_consultation_history', methods: ['GET'])]
    public function getPatientHistory(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $this->logger->info('Fetching patient history for patient ID: ' . $id);
            
            $cacheKey = "patient_history_$id";
            
            if ($this->cache) {
                $history = $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
                    $item->expiresAfter(300); // 5 minutes cache
                    return $this->buildPatientHistory($id);
                });
            } else {
                $history = $this->buildPatientHistory($id);
            }
            
            $this->logger->info('Patient history result count: ' . count($history));
            
            return new JsonResponse($history);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching patient history: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch patient history'], 500);
        }
    }
    
    private function buildPatientHistory(int $patientId): array
    {
        $this->logger->info('Building patient history for patient ID: ' . $patientId);
        
        $consultations = $this->entityManager->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->where('c.patient = :patientId')
            ->setParameter('patientId', $patientId)
            ->orderBy('c.consultationDate', 'DESC')
            ->setMaxResults(50) // Limit to last 50 consultations
            ->getQuery()
            ->getResult();

        $this->logger->info('Found ' . count($consultations) . ' consultations for patient ID: ' . $patientId);

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

            // Check for payment records to get accurate payment status
            $paymentRecords = $this->entityManager->getRepository(\App\Entity\Payment::class)
                ->findBy(['consultation' => $consultation]);
            
            // Convert payment records to simple array to avoid serialization issues
            $paymentData = [];
            foreach ($paymentRecords as $payment) {
                $paymentData[] = [
                    'id' => $payment->getId(),
                    'amount' => $payment->getAmount(),
                    'paymentMethod' => $payment->getPaymentMethod(),
                    'paymentDate' => $payment->getPaymentDate() ? $payment->getPaymentDate()->format('Y-m-d H:i:s') : null
                ];
            }
            
            $hasPaymentRecord = count($paymentRecords) > 0;
            $isActuallyPaid = $hasPaymentRecord || $consultation->getIsPaid();

            try {
                $doctorData = null;
                if ($consultation->getDoctor()) {
                    $doctorData = [
                        'id' => $consultation->getDoctor()->getId(),
                        'name' => $consultation->getDoctor()->getName()
                    ];
                }
                
                $patientData = null;
                if ($consultation->getPatient()) {
                    $patientData = [
                        'id' => $consultation->getPatient()->getId(),
                        'name' => $consultation->getPatient()->getName()
                    ];
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
                    'isPaid' => $isActuallyPaid, // Use enhanced payment status check
                    'payments' => $paymentData, // Include payment records for frontend reference
                    'status' => $isActuallyPaid ? 'Paid' : 'Unpaid',
                    'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null,
                    'hasMedicalCertificate' => $consultation->getHasMedicalCertificate() ?? false,
                    'mcStartDate' => $consultation->getMcStartDate() ? $consultation->getMcStartDate()->format('Y-m-d') : null,
                    'mcEndDate' => $consultation->getMcEndDate() ? $consultation->getMcEndDate()->format('Y-m-d') : null,
                    'mcNotes' => method_exists($consultation, 'getMcNotes') ? $consultation->getMcNotes() : null,
                    'mcNumber' => method_exists($consultation, 'getMcNumber') ? $consultation->getMcNumber() : null,
                    'queueNumber' => method_exists($consultation, 'getQueueNumber') ? $consultation->getQueueNumber() : null,
                    'doctor' => $doctorData,
                    'patient' => $patientData,
                    'receiptNumber' => method_exists($consultation, 'getReceiptNumber') ? $consultation->getReceiptNumber() : null
                ];
                
                $this->logger->info('Added consultation to history', [
                    'consultationId' => $consultation->getId(),
                    'patientId' => $consultation->getPatient()->getId(),
                    'doctorId' => $consultation->getDoctor()->getId()
                ]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error processing consultation for history', [
                    'consultationId' => $consultation->getId(),
                    'error' => $e->getMessage()
                ]);
                continue; // Skip this consultation if there's an error
            }
        }
        
        return $history;
    }

    #[Route('', name: 'app_consultations_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $date = $request->query->get('date');
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(10, (int) $request->query->get('limit', 20))); // Max 50, min 10
            
            $cacheKey = 'consultations_list_' . ($date ?: 'all') . '_page_' . $page . '_limit_' . $limit;
            
            if ($this->cache) {
                $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($date, $page, $limit) {
                    $item->expiresAfter(120); // 2 minutes cache
                    return $this->buildConsultationsList($date, $page, $limit);
                });
            } else {
                $data = $this->buildConsultationsList($date, $page, $limit);
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching consultations list: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch consultations'], 500);
        }
    }
    
    private function buildConsultationsList(?string $date, int $page, int $limit): array
    {
        $qb = $this->entityManager->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->select('c', 'p', 'd') // Select related entities to avoid N+1 queries
            ->leftJoin('c.patient', 'p')
            ->leftJoin('c.doctor', 'd')
            ->orderBy('c.createdAt', 'DESC');
        
        if ($date) {
            // Filter by specific date - use both createdAt and consultationDate for broader matching
            $start = \App\Service\TimezoneService::startOfDay($date);
            $end = \App\Service\TimezoneService::endOfDay($date);
            
            $qb->where('(c.createdAt BETWEEN :start AND :end) OR (c.consultationDate BETWEEN :start AND :end)')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        } else {
            // Return consultations from the last 30 days if no date filter (performance optimization)
            $thirtyDaysAgo = \App\Service\TimezoneService::createDateTime('-30 days');
            $qb->where('c.createdAt >= :thirtyDaysAgo')
               ->setParameter('thirtyDaysAgo', $thirtyDaysAgo);
        }
        
        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        
        $consultations = $qb->getQuery()->getResult();
        $data = [];

        foreach ($consultations as $consultation) {
            try {
                $patient = $consultation->getPatient();
                $doctor = $consultation->getDoctor();
                
                if (!$patient || !$doctor) {
                    continue; // Skip incomplete records
                }
                
                // Get prescribed medications efficiently
                $prescribedMeds = $this->entityManager->getRepository(\App\Entity\PrescribedMedication::class)
                    ->findBy(['consultation' => $consultation]);
                
                $medicationsArray = [];
                foreach ($prescribedMeds as $med) {
                    $medicationsArray[] = [
                        'id' => $med->getId(),
                        'name' => $med->getName(),
                        'quantity' => $med->getQuantity(),
                        'dosage' => $med->getDosage(),
                        'frequency' => $med->getFrequency(),
                        'duration' => $med->getDuration(),
                        'instructions' => $med->getInstructions(),
                        'actualPrice' => $med->getActualPrice(),
                        'medication' => $med->getMedication() ? [
                            'id' => $med->getMedication()->getId(),
                            'name' => $med->getMedication()->getName(),
                            'category' => $med->getMedication()->getCategory(),
                            'unitType' => $med->getMedication()->getUnitType()
                        ] : null
                    ];
                }
                
                // Determine status with a fallback for older records
                $status = $consultation->getStatus();
                if ($status === null) {
                    $status = $consultation->getIsPaid() ? 'completed' : 'pending';
                }

                $data[] = [
                    'id' => $consultation->getId(),
                    'patientId' => $patient->getId(),
                    'patientName' => $patient->getName(),
                    'doctorId' => $doctor->getId(),
                    'doctorName' => $doctor->getName(),
                    'createdAt' => $consultation->getCreatedAt()->format('Y-m-d H:i:s'),
                    'consultationDate' => $consultation->getConsultationDate() ? $consultation->getConsultationDate()->format('Y-m-d H:i:s') : $consultation->getCreatedAt()->format('Y-m-d H:i:s'),
                    'symptoms' => $consultation->getSymptoms(),
                    'diagnosis' => $consultation->getDiagnosis(),
                    'treatment' => $consultation->getTreatment(),
                    'notes' => $consultation->getNotes(),
                    'remarks' => $consultation->getNotes(),
                    'medications' => $consultation->getMedications(),
                    'prescribedMedications' => $medicationsArray,
                    'consultationFee' => $consultation->getConsultationFee(),
                    'medicinesFee' => $consultation->getMedicinesFee(),
                    'totalAmount' => $consultation->getTotalAmount(),
                    'isPaid' => $consultation->getIsPaid(),
                    'status' => $status,
                    'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null,
                    'receiptNumber' => $consultation->getReceiptNumber()
                ];
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Failed to build data for consultation ID: ' . ($consultation ? $consultation->getId() : 'unknown'),
                    [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                );
                continue;
            }
        }

        return $data;
    }

    #[Route('/ongoing', name: 'app_consultations_ongoing', methods: ['GET'], priority: 1)]
    public function ongoing(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $doctorId = $request->query->get('doctorId');
            
            // Temporarily disable cache and add debugging
            $this->logger->info('Starting buildOngoingData', ['doctorId' => $doctorId]);
            $data = $this->buildOngoingData($doctorId);
            $this->logger->info('buildOngoingData completed successfully', ['resultCount' => count($data['ongoing'] ?? [])]);
            
            return new JsonResponse($data);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->logger->error('Database error fetching ongoing consultations: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Database error occurred',
                'message' => 'Unable to fetch ongoing consultations due to database issue'
            ], 500);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching ongoing consultations: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Failed to fetch ongoing consultations',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    #[Route('/today-all', name: 'app_consultations_today_all', methods: ['GET'])]
    public function todayAll(Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $doctorId = $request->query->get('doctorId');
            $cacheKey = 'consultations_today_all' . ($doctorId ? '_doctor_' . $doctorId : '');
            
            if ($this->cache) {
                $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($doctorId) {
                    $item->expiresAfter(30); // 30 seconds cache
                    return $this->buildTodayAllData($doctorId);
                });
            } else {
                $data = $this->buildTodayAllData($doctorId);
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching today\'s patients: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch today\'s patients'], 500);
        }
    }
    
    private function buildOngoingData($doctorId = null): array
    {
        try {
            $today = \App\Service\TimezoneService::now();
            
            // Set time to the beginning of the day for the start of the range
            $startOfDay = (clone $today)->setTime(0, 0, 0);
            
            // Set time to the end of the day for the end of the range
            $endOfDay = (clone $today)->setTime(23, 59, 59);

            $ongoingData = [];

            // Get ongoing consultations for the current day
            $ongoingConsultations = $this->getOngoingConsultations($startOfDay, $endOfDay, $doctorId);
            $ongoingData = array_merge($ongoingData, $ongoingConsultations);

            // Get queue entries that are waiting for consultation
            $queueEntries = $this->getOngoingQueueEntries($startOfDay, $endOfDay, $doctorId);
            $ongoingData = array_merge($ongoingData, $queueEntries);

            // Sort by queue number and consultation date
            usort($ongoingData, function($a, $b) {
                if ($a['isQueueEntry'] && $b['isQueueEntry']) {
                    return strcmp($a['queueNumber'] ?? '', $b['queueNumber'] ?? '');
                } elseif ($a['isQueueEntry']) {
                    return -1; // Queue entries first
                } elseif ($b['isQueueEntry']) {
                    return 1;
                } else {
                    // Both are consultations - compare by date
                    $dateA = $a['consultationDate'] ?? '';
                    $dateB = $b['consultationDate'] ?? '';
                    return strcmp($dateA, $dateB);
                }
            });
                
            // Get total unique patients for today
            $todayTotal = $this->getTodayTotalPatients($startOfDay, $endOfDay, $doctorId);

            return [
                'ongoing' => $ongoingData,
                'todayTotal' => $todayTotal
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error in buildOngoingData: ' . $e->getMessage(), [
                'doctorId' => $doctorId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty data instead of throwing
            return [
                'ongoing' => [],
                'todayTotal' => 0
            ];
        }
    }

    /**
     * Get ongoing consultations for the specified time range
     */
    private function getOngoingConsultations(\DateTimeInterface $startOfDay, \DateTimeInterface $endOfDay, $doctorId = null): array
    {
        $consultationQb = $this->entityManager->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->select('c.id', 'c.consultationDate', 'p.name as patientName', 'd.name as doctorName', 'c.status', 'c.symptoms', 'p.id as patientId', 'd.id as doctorId')
            ->join('c.patient', 'p')
            ->join('c.doctor', 'd')
            ->where('c.status NOT IN (:completed_statuses)')
            ->andWhere('c.consultationDate BETWEEN :start AND :end')
            ->setParameter('completed_statuses', ['completed', 'completed_consultation'])
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay);

        if ($doctorId) {
            $consultationQb->andWhere('d.id = :doctorId')
                          ->setParameter('doctorId', $doctorId);
        }

        $consultations = $consultationQb->orderBy('c.consultationDate', 'ASC')
            ->getQuery()
            ->getResult();

        $ongoingData = [];
        foreach ($consultations as $consultation) {
            $ongoingData[] = [
                'id' => $consultation['id'],
                'consultationDate' => $consultation['consultationDate'] instanceof \DateTimeInterface
                    ? $consultation['consultationDate']->format('Y-m-d H:i:s')
                    : $consultation['consultationDate'],
                'patientName' => $consultation['patientName'],
                'patientId' => $consultation['patientId'],
                'doctorName' => $consultation['doctorName'],
                'doctorId' => $consultation['doctorId'],
                'status' => $consultation['status'],
                'symptoms' => $consultation['symptoms'],
                'isQueueEntry' => false,
                'queueId' => null,
                'queueNumber' => null
            ];
        }

        return $ongoingData;
    }

    /**
     * Get ongoing queue entries for the specified time range
     */
    private function getOngoingQueueEntries(\DateTimeInterface $startOfDay, \DateTimeInterface $endOfDay, $doctorId = null): array
    {
        $queueQb = $this->entityManager->getRepository(Queue::class)
            ->createQueryBuilder('q')
            ->select('q', 'p', 'd')
            ->join('q.patient', 'p')
            ->join('q.doctor', 'd')
            ->where('q.status IN (:statuses)')
            ->andWhere('q.queueDateTime BETWEEN :start AND :end')
            ->setParameter('statuses', ['waiting', 'in_consultation'])
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay);
            
        if ($doctorId) {
            $queueQb->andWhere('d.id = :queueDoctorId')
                    ->setParameter('queueDoctorId', $doctorId);
        }
        
        $queueEntries = $queueQb->orderBy('q.queueNumber', 'ASC')
            ->getQuery()
            ->getResult();

        $ongoingData = [];
        $groupedQueues = [];

        foreach ($queueEntries as $queue) {
            $patient = $queue->getPatient();
            $doctor = $queue->getDoctor();
            
            if (!$patient || !$doctor) {
                $this->logger->warning('Skipping queue entry with missing patient or doctor', [
                    'queueId' => $queue->getId(),
                    'patientId' => $patient?->getId(),
                    'doctorId' => $doctor?->getId()
                ]);
                continue;
            }
            
            // Check if this is a group consultation
            $groupId = $queue->getGroupId();
            $isGroupConsultation = $queue->isGroupConsultation();
            
            if ($groupId && $isGroupConsultation) {
                // Handle group consultation
                if (!isset($groupedQueues[$groupId])) {
                    $groupData = $this->buildGroupConsultationData($queue, $groupId);
                    if ($groupData) {
                        $groupedQueues[$groupId] = $groupData;
                    }
                }
            } else {
                // Single patient queue
                $ongoingData[] = [
                    'id' => null,
                    'consultationDate' => $queue->getQueueDateTime()->format('Y-m-d H:i:s'),
                    'patientName' => $patient->getName(),
                    'patientId' => $patient->getId(),
                    'doctorName' => $doctor->getName(),
                    'doctorId' => $doctor->getId(),
                    'status' => $queue->getStatus(),
                    'symptoms' => $patient->getRemarks(),
                    'isQueueEntry' => true,
                    'queueId' => $queue->getId(),
                    'queueNumber' => $queue->getQueueNumber(),
                    'isGroupConsultation' => false
                ];
            }
        }

        // Add grouped consultations to ongoing data
        foreach ($groupedQueues as $group) {
            $ongoingData[] = $group;
        }

        return $ongoingData;
    }

    /**
     * Build data for group consultation
     */
    private function buildGroupConsultationData($queue, $groupId): ?array
    {
        try {
            $patient = $queue->getPatient();
            $doctor = $queue->getDoctor();
            
            // Fetch all patients in this group
            $allGroupQueues = $this->entityManager->getRepository(Queue::class)
                ->createQueryBuilder('gq')
                ->select('gq', 'gp')
                ->join('gq.patient', 'gp')
                ->where('gq.metadata LIKE :groupId')
                ->setParameter('groupId', '%"groupId":"' . $groupId . '"%')
                ->getQuery()
                ->getResult();
            
            $groupPatients = [];
            foreach ($allGroupQueues as $groupQueue) {
                $groupPatient = $groupQueue->getPatient();
                if ($groupPatient) {
                    $groupPatients[] = [
                        'patientName' => $groupPatient->getName(),
                        'patientId' => $groupPatient->getId(),
                        'symptoms' => $groupPatient->getRemarks()
                    ];
                }
            }
            
            if (empty($groupPatients)) {
                $this->logger->warning('Group consultation has no patients', ['groupId' => $groupId]);
                return null;
            }
            
            return [
                'id' => null,
                'consultationDate' => $queue->getQueueDateTime()->format('Y-m-d H:i:s'),
                'doctorName' => $doctor->getName(),
                'doctorId' => $doctor->getId(),
                'status' => $queue->getStatus(),
                'isQueueEntry' => true,
                'queueId' => $queue->getId(),
                'queueNumber' => $queue->getQueueNumber(),
                'isGroupConsultation' => true,
                'patients' => $groupPatients
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error building group consultation data: ' . $e->getMessage(), [
                'groupId' => $groupId,
                'queueId' => $queue->getId()
            ]);
            return null;
        }
    }

    /**
     * Get total unique patients for today
     */
    private function getTodayTotalPatients(\DateTimeInterface $startOfDay, \DateTimeInterface $endOfDay, $doctorId = null): int
    {
        try {
            $queueQb = $this->entityManager->getRepository(Queue::class)
                ->createQueryBuilder('q')
                ->select('COUNT(DISTINCT IDENTITY(q.patient))')
                ->where('q.queueDateTime BETWEEN :start AND :end')
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay);
                
            if ($doctorId) {
                $queueQb->join('q.doctor', 'd')
                       ->andWhere('d.id = :doctorId')
                       ->setParameter('doctorId', $doctorId);
            }
            
            return (int) $queueQb->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            $this->logger->error('Error getting today total patients: ' . $e->getMessage());
            return 0;
        }
    }

    private function buildTodayAllData($doctorId = null): array
    {
        $today = \App\Service\TimezoneService::now();
        
        // Set time to the beginning of the day for the start of the range
        $startOfDay = (clone $today)->setTime(0, 0, 0);
        
        // Set time to the end of the day for the end of the range
        $endOfDay = (clone $today)->setTime(23, 59, 59);

        $allPatients = [];

        // Get all consultations for today (including completed ones)
        $consultationQb = $this->entityManager->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->select('c.id', 'c.consultationDate', 'c.status', 'c.totalAmount', 'c.isPaid', 'c.paidAt', 'p.name as patientName', 'd.name as doctorName', 'p.id as patientId', 'd.id as doctorId', 'c.createdAt')
            ->join('c.patient', 'p')
            ->join('c.doctor', 'd')
            ->where('c.consultationDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay);

        if ($doctorId) {
            $consultationQb->andWhere('d.id = :doctorId')
                          ->setParameter('doctorId', $doctorId);
        }

        $consultations = $consultationQb->orderBy('c.consultationDate', 'DESC')
            ->getQuery()
            ->getResult();

        // Add consultations to the data
        foreach ($consultations as $consultation) {
            // Properly format the consultation date/time
            $consultationTime = null;
            if ($consultation['consultationDate'] instanceof \DateTimeInterface) {
                $consultationTime = $consultation['consultationDate']->format('Y-m-d H:i:s');
            } elseif ($consultation['consultationDate']) {
                $consultationTime = $consultation['consultationDate'];
            } elseif ($consultation['createdAt'] instanceof \DateTimeInterface) {
                // Fallback to createdAt if consultationDate is null
                $consultationTime = $consultation['createdAt']->format('Y-m-d H:i:s');
            }

            $allPatients[] = [
                'id' => $consultation['id'],
                'type' => 'consultation',
                'time' => $consultationTime,
                'patientName' => $consultation['patientName'],
                'patientId' => $consultation['patientId'],
                'doctorName' => $consultation['doctorName'],
                'doctorId' => $consultation['doctorId'],
                'status' => $consultation['status'] ?: 'completed',
                'queueNumber' => null,
                'queueId' => null,
                'totalAmount' => $consultation['totalAmount'] ?: '0.00',
                'isPaid' => $consultation['isPaid'] ?: false,
                'paidAt' => $consultation['paidAt'] ? $consultation['paidAt']->format('Y-m-d H:i:s') : null,
                'paymentStatus' => $consultation['isPaid'] ? 'paid' : 'pending'
            ];
        }

        // Get all queue entries for today (including completed ones)
        $queueQb = $this->entityManager->getRepository(Queue::class)
            ->createQueryBuilder('q')
            ->select('q.id as queueId', 'q.queueNumber', 'q.queueDateTime', 'q.status', 'q.amount', 'q.isPaid', 'q.paidAt', 'q.paymentMethod', 'p.name as patientName', 'p.id as patientId', 'd.name as doctorName', 'd.id as doctorId')
            ->join('q.patient', 'p')
            ->join('q.doctor', 'd')
            ->where('q.queueDateTime BETWEEN :start AND :end')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay);
            
        if ($doctorId) {
            $queueQb->andWhere('d.id = :queueDoctorId')
                    ->setParameter('queueDoctorId', $doctorId);
        }
        
        $queueEntries = $queueQb->orderBy('q.queueDateTime', 'DESC')
            ->getQuery()
            ->getResult();

        // Add queue entries to the data
        foreach ($queueEntries as $queue) {
            // Properly format the queue date/time
            $queueTime = null;
            if ($queue['queueDateTime'] instanceof \DateTimeInterface) {
                $queueTime = $queue['queueDateTime']->format('Y-m-d H:i:s');
            } elseif ($queue['queueDateTime']) {
                $queueTime = $queue['queueDateTime'];
            }

            $allPatients[] = [
                'id' => $queue['queueId'],
                'type' => 'queue',
                'time' => $queueTime,
                'patientName' => $queue['patientName'],
                'patientId' => $queue['patientId'],
                'doctorName' => $queue['doctorName'],
                'doctorId' => $queue['doctorId'],
                'status' => $queue['status'],
                'queueNumber' => $queue['queueNumber'],
                'queueId' => $queue['queueId'],
                'totalAmount' => $queue['amount'] ?: '0.00',
                'isPaid' => $queue['isPaid'] ?: false,
                'paidAt' => $queue['paidAt'] ? $queue['paidAt']->format('Y-m-d H:i:s') : null,
                'paymentMethod' => $queue['paymentMethod'],
                'paymentStatus' => $queue['isPaid'] ? 'paid' : 'pending'
            ];
        }

        // Sort by time
        usort($allPatients, function($a, $b) {
            $timeA = $a['time'] instanceof \DateTimeInterface ? $a['time'] : \App\Service\TimezoneService::createDateTime($a['time']);
            $timeB = $b['time'] instanceof \DateTimeInterface ? $b['time'] : \App\Service\TimezoneService::createDateTime($b['time']);
            return $timeA <=> $timeB;
        });

        return [
            'patients' => $allPatients
        ];
    }

    #[Route('/{id}', name: 'app_consultations_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $consultation = $this->entityManager->getRepository(Consultation::class)->find($id);
            
            if (!$consultation) {
                return new JsonResponse(['message' => 'Consultation not found'], 404);
            }

            $patient = $consultation->getPatient();
            $doctor = $consultation->getDoctor();
            
            // Get prescribed medications
            $prescribedMeds = $this->entityManager->getRepository(\App\Entity\PrescribedMedication::class)
                ->createQueryBuilder('pm')
                ->leftJoin('pm.medication', 'm')
                ->where('pm.consultation = :consultationId')
                ->setParameter('consultationId', $id)
                ->orderBy('pm.prescribedAt', 'ASC')
                ->getQuery()
                ->getResult();
            
            $medicationsData = [];
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
                ];
            }
            
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
                'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null,
                'receiptNumber' => method_exists($consultation, 'getReceiptNumber') ? $consultation->getReceiptNumber() : null,
                'prescribedMedications' => $medicationsData
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching consultation: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch consultation'], 500);
        }
    }

    #[Route('/{id}/payment', name: 'app_consultation_payment', methods: ['POST'])]
    public function updatePaymentStatus(
        int $id,
        Request $request
    ): JsonResponse {
        try {
            $consultation = $this->entityManager->getRepository(Consultation::class)->find($id);
            
            if (!$consultation) {
                return new JsonResponse(['message' => 'Consultation not found'], 404);
            }
            
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['isPaid'])) {
                return new JsonResponse(['error' => 'isPaid field is required'], 400);
            }
            
            $consultation->setIsPaid($data['isPaid']);
            
            // Set payment timestamp if marking as paid
            if ($data['isPaid']) {
                $consultation->setPaidAt(\App\Service\TimezoneService::now());
            } else {
                $consultation->setPaidAt(null);
            }
            
            // Update payment method if provided - also update the Payment entity
            if (isset($data['paymentMethod'])) {
                if (method_exists($consultation, 'setPaymentMethod')) {
                    $consultation->setPaymentMethod($data['paymentMethod']);
                }
                
                // Also update the Payment entity
                $paymentRepository = $this->entityManager->getRepository(\App\Entity\Payment::class);
                $payment = $paymentRepository->findOneBy(['consultation' => $consultation]);
                if ($payment) {
                    $payment->setPaymentMethod($data['paymentMethod']);
                    $this->logger->info('Updated payment method in Payment entity', [
                        'paymentId' => $payment->getId(),
                        'paymentMethod' => $data['paymentMethod']
                    ]);
                }
            }
            
            // Also update the consultation status
            if ($consultation->getStatus() === 'completed_consultation') {
                $consultation->setStatus('completed');
            }
            
            $this->entityManager->flush();
            
            // Clear relevant caches
            if ($this->cache) {
                $today = date('Y-m-d');
                $cacheKeys = [
                    'consultations_list_' . $today,
                    'patient_history_' . $consultation->getPatient()->getId(),
                    // Clear queue caches for immediate updates
                    'queue_list_' . $today . '_all_page_1_limit_50',
                    'queue_list_' . $today . '_waiting_page_1_limit_50',
                    'queue_list_' . $today . '_in_consultation_page_1_limit_50',
                    'queue_list_' . $today . '_completed_page_1_limit_50',
                    'queue_stats'
                ];
                
                foreach ($cacheKeys as $key) {
                    $this->cache->delete($key);
                }
            }
            
            return new JsonResponse([
                'message' => 'Payment status updated successfully',
                'isPaid' => $consultation->getIsPaid(),
                'paidAt' => $consultation->getPaidAt() ? $consultation->getPaidAt()->format('Y-m-d H:i:s') : null
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error updating payment status: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Error updating payment status'], 500);
        }
    }

    #[Route('/{id}/medications', name: 'app_consultation_medications', methods: ['GET'])]
    public function getConsultationMedications(int $id, Request $request): JsonResponse
    {
        // Rate limiting
        if (!$this->checkRateLimit($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        try {
            $consultation = $this->entityManager->getRepository(Consultation::class)->find($id);
            
            if (!$consultation) {
                return new JsonResponse(['message' => 'Consultation not found'], 404);
            }
            
            $this->logger->info('Fetching medications for consultation ID: ' . $id);
            
            // Get prescribed medications from PrescribedMedication entities
            $prescribedMeds = $this->entityManager->getRepository(\App\Entity\PrescribedMedication::class)
                ->createQueryBuilder('pm')
                ->leftJoin('pm.medication', 'm')
                ->where('pm.consultation = :consultationId')
                ->setParameter('consultationId', $id)
                ->orderBy('pm.prescribedAt', 'ASC')
                ->getQuery()
                ->getResult();
            
            $medicationsData = [];
            
            $this->logger->info('Found ' . count($prescribedMeds) . ' prescribed medications for consultation ID: ' . $id);
            
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
                if ($consultation->getMedications()) {
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
            
            $this->logger->info('Returning ' . count($medicationsData) . ' medications for consultation ID: ' . $id);
            
            return new JsonResponse($medicationsData);
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching consultation medications: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to fetch medications'], 500);
        }
    }
}
