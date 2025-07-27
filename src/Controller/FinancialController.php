<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\ConsultationRepository;
use App\Repository\PrescribedMedicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/financial')]
class FinancialController extends AbstractController
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private ConsultationRepository $consultationRepository,
        private PrescribedMedicationRepository $prescribedMedicationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/dashboard', name: 'api_financial_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'today'); // today, week, month, quarter, year, all

        $startDate = $this->getStartDate($period);
        $endDate = \App\Service\TimezoneService::now();

        // Current period stats
        $currentStats = $this->getDailyStats($startDate, $endDate);
        
        // Previous period stats for trends
        $previousPeriodStats = $this->getPreviousPeriodStats($period);
        
        // Calculate trends
        $trends = $this->calculateTrends($currentStats, $previousPeriodStats);
        
        // Monthly stats
        $monthlyStats = $this->getMonthlyStats();
        
        // Medication usage stats
        $medicationStats = $this->getMedicationStats($startDate, $endDate);
        
        // Payment method breakdown
        $paymentMethodStats = $this->getPaymentMethodStats($startDate, $endDate);
        
        // Recent transactions
        $recentTransactions = $this->getRecentTransactions(10);

        return new JsonResponse([
            'period' => $period,
            'daily_stats' => $currentStats,
            'monthly_stats' => $monthlyStats,
            'trends' => $trends,
            'medication_stats' => $medicationStats,
            'payment_method_stats' => $paymentMethodStats,
            'recent_transactions' => $recentTransactions,
            'summary' => [
                'total_revenue' => $currentStats['total_amount'],
                'total_consultations' => $currentStats['total_consultations'],
                'average_per_consultation' => $currentStats['total_consultations'] > 0 
                    ? round($currentStats['total_amount'] / $currentStats['total_consultations'], 2) 
                    : 0
            ]
        ]);
    }

    #[Route('/revenue-chart', name: 'api_financial_revenue_chart', methods: ['GET'])]
    public function revenueChart(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month'); // week, month, quarter, year
        $chartData = $this->getRevenueChartData($period);

        return new JsonResponse($chartData);
    }

    #[Route('/payments', name: 'api_financial_payments', methods: ['GET'])]
    public function payments(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 50);
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $paymentMethod = $request->query->get('payment_method');

        // Build query with all payment details including who processed it
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->leftJoin('p.consultation', 'c')
            ->leftJoin('c.patient', 'patient')
            ->leftJoin('c.doctor', 'doctor')
            ->leftJoin('p.processedBy', 'staff')
            ->leftJoin('p.queue', 'q')
            ->orderBy('p.paymentDate', 'DESC');

        // Apply filters (using date range comparison with timezone handling)
        if ($startDate) {
            // Convert start date to UTC for comparison
            $startDateTime = \App\Service\TimezoneService::startOfDay($startDate);
            $startDateTime = \App\Service\TimezoneService::convertToUtc($startDateTime);
            $qb->andWhere('p.paymentDate >= :startDate')
               ->setParameter('startDate', $startDateTime);
        }
        if ($endDate) {
            // Convert end date to UTC for comparison
            $endDateTime = \App\Service\TimezoneService::endOfDay($endDate);
            $endDateTime = \App\Service\TimezoneService::convertToUtc($endDateTime);
            $qb->andWhere('p.paymentDate <= :endDate')
               ->setParameter('endDate', $endDateTime);
        }
        if ($paymentMethod) {
            $qb->andWhere('p.paymentMethod = :paymentMethod')
               ->setParameter('paymentMethod', $paymentMethod);
        }

        // Default to today if no date filter (using Malaysia timezone)
        if (!$startDate && !$endDate) {
            $todayStart = \App\Service\TimezoneService::startOfDay();
            $todayEnd = \App\Service\TimezoneService::endOfDay();
            $todayStart = \App\Service\TimezoneService::convertToUtc($todayStart);
            $todayEnd = \App\Service\TimezoneService::convertToUtc($todayEnd);
            
            $qb->andWhere('p.paymentDate BETWEEN :todayStart AND :todayEnd')
               ->setParameter('todayStart', $todayStart)
               ->setParameter('todayEnd', $todayEnd);
        }

        // Get total count
        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(DISTINCT p.id)')->getQuery()->getSingleScalarResult();

        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $payments = $qb->getQuery()->getResult();

        $data = array_map(function ($payment) {
            $consultation = $payment->getConsultation();
            $queue = $payment->getQueue();
            $processedBy = $payment->getProcessedBy();
            
            // Get medicines information - try multiple sources
            $medicines = [];
            if ($consultation) {
                // First try prescribed medications table
                $prescribedMeds = $this->prescribedMedicationRepository->findBy(['consultation' => $consultation]);
                foreach ($prescribedMeds as $pm) {
                    $medication = $pm->getMedication();
                    if ($medication) {
                        $medicines[] = [
                            'name' => $medication->getName(),
                            'dosage' => $pm->getDosage() ?? 'N/A',
                            'frequency' => $pm->getFrequency() ?? 'N/A',
                            'duration' => $pm->getDuration() ?? 'N/A',
                            'quantity' => $pm->getQuantity() ?? 'N/A'
                        ];
                    }
                }
                
                // If no prescribed medications found, try to parse from consultation.medications JSON
                if (empty($medicines) && $consultation->getMedications()) {
                    $medicationsJson = $consultation->getMedications();
                    $decoded = json_decode($medicationsJson, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        foreach ($decoded as $med) {
                            $medicines[] = [
                                'name' => $med['name'] ?? $med['medication'] ?? 'Unknown Medicine',
                                'dosage' => $med['dosage'] ?? 'N/A',
                                'frequency' => $med['frequency'] ?? 'N/A',
                                'duration' => $med['duration'] ?? 'N/A',
                                'quantity' => $med['quantity'] ?? 'N/A'
                            ];
                        }
                    }
                }
            }

            return [
                'id' => $payment->getId(),
                'amount' => $payment->getAmount(),
                'payment_method' => $payment->getPaymentMethod(),
                'payment_date' => $payment->getPaymentDate()->format('Y-m-d H:i:s'),
                'payment_time' => $payment->getPaymentDate()->format('h:i A'),
                'reference' => $payment->getReference(),
                'notes' => $payment->getNotes(),
                'queue_number' => $payment->getQueueNumber(),
                'processed_by' => [
                    'id' => $processedBy ? $processedBy->getId() : null,
                    'name' => $processedBy ? $processedBy->getName() : 'System',
                    'email' => $processedBy ? $processedBy->getEmail() : null,
                ],
                'patient' => $consultation ? [
                    'id' => $consultation->getPatient()->getId(),
                    'name' => $consultation->getPatient()->getName(),
                    'nric' => $consultation->getPatient()->getNric(),
                    'phone' => $consultation->getPatient()->getPhone(),
                ] : null,
                'doctor' => $consultation ? [
                    'id' => $consultation->getDoctor()->getId(),
                    'name' => $consultation->getDoctor()->getName(),
                ] : null,
                'consultation' => $consultation ? [
                    'id' => $consultation->getId(),
                    'consultation_fee' => $consultation->getConsultationFee(),
                    'medicines_fee' => $consultation->getMedicinesFee(),
                ] : null,
                'medicines' => $medicines,
                'medicines_count' => count($medicines),
                'medicines_summary' => count($medicines) > 0 
                    ? implode(', ', array_slice(array_column($medicines, 'name'), 0, 3)) 
                    . (count($medicines) > 3 ? ' (+' . (count($medicines) - 3) . ' more)' : '')
                    : 'No medicines'
            ];
        }, $payments);

        return new JsonResponse([
            'data' => $data,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_method' => $paymentMethod,
            ]
        ]);
    }

    #[Route('/payments/summary', name: 'api_payments_summary', methods: ['GET'])]
    public function paymentsSummary(): JsonResponse
    {
        return new JsonResponse($this->paymentRepository->getSummary());
    }

    #[Route('/payments/all', name: 'api_financial_payments_all', methods: ['GET'])]
    public function paymentsAll(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 50);
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $paymentMethod = $request->query->get('payment_method');

        $allPayments = [];

        // 1. Get completed payments (from Payment table)
        $completedPayments = $this->getCompletedPayments($startDate, $endDate, $paymentMethod);
        
        // 2. Get pending payments (from Queue/Consultation with amounts but no Payment record)
        $pendingPayments = $this->getPendingPayments($startDate, $endDate);

        // Combine and sort by date
        $allPayments = array_merge($completedPayments, $pendingPayments);
        
        // Sort by date/time (newest first)
        usort($allPayments, function($a, $b) {
            $timeA = $a['payment_date'] ?? $a['consultation_date'] ?? '1970-01-01 00:00:00';
            $timeB = $b['payment_date'] ?? $b['consultation_date'] ?? '1970-01-01 00:00:00';
            return strcmp($timeB, $timeA);
        });

        // Apply pagination
        $total = count($allPayments);
        $offset = ($page - 1) * $limit;
        $paginatedPayments = array_slice($allPayments, $offset, $limit);

        return new JsonResponse([
            'data' => $paginatedPayments,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_method' => $paymentMethod,
            ]
        ]);
    }

    private function getCompletedPayments(?string $startDate, ?string $endDate, ?string $paymentMethod): array
    {
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->leftJoin('p.consultation', 'c')
            ->leftJoin('c.patient', 'patient')
            ->leftJoin('c.doctor', 'doctor')
            ->leftJoin('p.processedBy', 'staff')
            ->leftJoin('p.queue', 'q')
            ->orderBy('p.paymentDate', 'DESC');

        // Apply filters
        if ($startDate) {
            $startDateTime = \App\Service\TimezoneService::startOfDay($startDate);
            $startDateTime = \App\Service\TimezoneService::convertToUtc($startDateTime);
            $qb->andWhere('p.paymentDate >= :startDate')
               ->setParameter('startDate', $startDateTime);
        }
        if ($endDate) {
            $endDateTime = \App\Service\TimezoneService::endOfDay($endDate);
            $endDateTime = \App\Service\TimezoneService::convertToUtc($endDateTime);
            $qb->andWhere('p.paymentDate <= :endDate')
               ->setParameter('endDate', $endDateTime);
        }
        if ($paymentMethod) {
            $qb->andWhere('p.paymentMethod = :paymentMethod')
               ->setParameter('paymentMethod', $paymentMethod);
        }

        // Default to today if no date filter
        if (!$startDate && !$endDate) {
            $todayStart = \App\Service\TimezoneService::startOfDay();
            $todayEnd = \App\Service\TimezoneService::endOfDay();
            $todayStart = \App\Service\TimezoneService::convertToUtc($todayStart);
            $todayEnd = \App\Service\TimezoneService::convertToUtc($todayEnd);
            
            $qb->andWhere('p.paymentDate BETWEEN :todayStart AND :todayEnd')
               ->setParameter('todayStart', $todayStart)
               ->setParameter('todayEnd', $todayEnd);
        }

        $payments = $qb->getQuery()->getResult();

        return array_map(function ($payment) {
            $consultation = $payment->getConsultation();
            $processedBy = $payment->getProcessedBy();
            
            // Get medicines information - try multiple sources
            $medicines = [];
            if ($consultation) {
                // First try prescribed medications table
                $prescribedMeds = $this->prescribedMedicationRepository->findBy(['consultation' => $consultation]);
                foreach ($prescribedMeds as $pm) {
                    $medication = $pm->getMedication();
                    if ($medication) {
                        $medicines[] = [
                            'name' => $medication->getName(),
                            'dosage' => $pm->getDosage() ?? 'N/A',
                            'frequency' => $pm->getFrequency() ?? 'N/A',
                            'duration' => $pm->getDuration() ?? 'N/A',
                            'quantity' => $pm->getQuantity() ?? 'N/A'
                        ];
                    }
                }
                
                // If no prescribed medications found, try to parse from consultation.medications JSON
                if (empty($medicines) && $consultation->getMedications()) {
                    $medicationsJson = $consultation->getMedications();
                    $decoded = json_decode($medicationsJson, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        foreach ($decoded as $med) {
                            $medicines[] = [
                                'name' => $med['name'] ?? $med['medication'] ?? 'Unknown Medicine',
                                'dosage' => $med['dosage'] ?? 'N/A',
                                'frequency' => $med['frequency'] ?? 'N/A',
                                'duration' => $med['duration'] ?? 'N/A',
                                'quantity' => $med['quantity'] ?? 'N/A'
                            ];
                        }
                    }
                }
            }

            return [
                'id' => $payment->getId(),
                'type' => 'completed',
                'amount' => number_format($payment->getAmount(), 2),
                'payment_method' => $payment->getPaymentMethod(),
                'payment_date' => $payment->getPaymentDate()->format('Y-m-d H:i:s'),
                'payment_time' => $payment->getPaymentDate()->format('h:i A'),
                'reference' => $payment->getReference(),
                'notes' => $payment->getNotes(),
                'queue_number' => $payment->getQueueNumber(),
                'status' => 'paid',
                'processed_by' => [
                    'id' => $processedBy ? $processedBy->getId() : null,
                    'name' => $processedBy ? $processedBy->getName() : 'System',
                    'email' => $processedBy ? $processedBy->getEmail() : null,
                ],
                'patient' => $consultation ? [
                    'id' => $consultation->getPatient()->getId(),
                    'name' => $consultation->getPatient()->getName(),
                    'nric' => $consultation->getPatient()->getNric(),
                    'phone' => $consultation->getPatient()->getPhone(),
                ] : null,
                'doctor' => $consultation ? [
                    'id' => $consultation->getDoctor()->getId(),
                    'name' => $consultation->getDoctor()->getName(),
                ] : null,
                'consultation' => $consultation ? [
                    'id' => $consultation->getId(),
                    'consultation_fee' => $consultation->getConsultationFee(),
                    'medicines_fee' => $consultation->getMedicinesFee(),
                ] : null,
                'medicines' => $medicines,
                'medicines_count' => count($medicines),
                'medicines_summary' => count($medicines) > 0 
                    ? implode(', ', array_slice(array_column($medicines, 'name'), 0, 3)) 
                    . (count($medicines) > 3 ? ' (+' . (count($medicines) - 3) . ' more)' : '')
                    : 'No medicines'
            ];
        }, $payments);
    }

    private function getPendingPayments(?string $startDate, ?string $endDate): array
    {
        // Get consultations with amounts but no payment records
        $qb = $this->consultationRepository->createQueryBuilder('c')
            ->leftJoin('c.patient', 'patient')
            ->leftJoin('c.doctor', 'doctor')
            ->leftJoin(\App\Entity\Payment::class, 'p', 'WITH', 'p.consultation = c.id')
            ->where('c.totalAmount > 0')
            ->andWhere('p.id IS NULL') // No payment record exists
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('statuses', ['completed_consultation', 'completed'])
            ->orderBy('c.consultationDate', 'DESC');

        // Apply date filters
        if ($startDate) {
            $startDateTime = \App\Service\TimezoneService::startOfDay($startDate);
            $startDateTime = \App\Service\TimezoneService::convertToUtc($startDateTime);
            $qb->andWhere('c.consultationDate >= :startDate')
               ->setParameter('startDate', $startDateTime);
        }
        if ($endDate) {
            $endDateTime = \App\Service\TimezoneService::endOfDay($endDate);
            $endDateTime = \App\Service\TimezoneService::convertToUtc($endDateTime);
            $qb->andWhere('c.consultationDate <= :endDate')
               ->setParameter('endDate', $endDateTime);
        }

        // Default to today if no date filter
        if (!$startDate && !$endDate) {
            $todayStart = \App\Service\TimezoneService::startOfDay();
            $todayEnd = \App\Service\TimezoneService::endOfDay();
            $todayStart = \App\Service\TimezoneService::convertToUtc($todayStart);
            $todayEnd = \App\Service\TimezoneService::convertToUtc($todayEnd);
            
            $qb->andWhere('c.consultationDate BETWEEN :todayStart AND :todayEnd')
               ->setParameter('todayStart', $todayStart)
               ->setParameter('todayEnd', $todayEnd);
        }

        $consultations = $qb->getQuery()->getResult();

        return array_map(function ($consultation) {
            // Get medicines information - try multiple sources
            $medicines = [];
            // First try prescribed medications table
            $prescribedMeds = $this->prescribedMedicationRepository->findBy(['consultation' => $consultation]);
            foreach ($prescribedMeds as $pm) {
                $medication = $pm->getMedication();
                if ($medication) {
                    $medicines[] = [
                        'name' => $medication->getName(),
                        'dosage' => $pm->getDosage() ?? 'N/A',
                        'frequency' => $pm->getFrequency() ?? 'N/A',
                        'duration' => $pm->getDuration() ?? 'N/A',
                        'quantity' => $pm->getQuantity() ?? 'N/A'
                    ];
                }
            }
            
            // If no prescribed medications found, try to parse from consultation.medications JSON
            if (empty($medicines) && $consultation->getMedications()) {
                $medicationsJson = $consultation->getMedications();
                $decoded = json_decode($medicationsJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    foreach ($decoded as $med) {
                        $medicines[] = [
                            'name' => $med['name'] ?? $med['medication'] ?? 'Unknown Medicine',
                            'dosage' => $med['dosage'] ?? 'N/A',
                            'frequency' => $med['frequency'] ?? 'N/A',
                            'duration' => $med['duration'] ?? 'N/A',
                            'quantity' => $med['quantity'] ?? 'N/A'
                        ];
                    }
                }
            }

            // Get queue information
            $queue = $this->entityManager->getRepository(\App\Entity\Queue::class)
                ->findOneBy(['patient' => $consultation->getPatient(), 'doctor' => $consultation->getDoctor()], ['id' => 'DESC']);

            return [
                'id' => $consultation->getId(),
                'queue_id' => $queue ? $queue->getId() : null,
                'type' => 'pending',
                'amount' => number_format($consultation->getTotalAmount(), 2),
                'payment_method' => null,
                'consultation_date' => $consultation->getConsultationDate()->format('Y-m-d H:i:s'),
                'payment_time' => $consultation->getConsultationDate()->format('h:i A'),
                'reference' => null,
                'notes' => null,
                'queue_number' => $queue ? $queue->getQueueNumber() : null,
                'status' => 'pending',
                'processed_by' => null,
                'patient' => [
                    'id' => $consultation->getPatient()->getId(),
                    'name' => $consultation->getPatient()->getName(),
                    'nric' => $consultation->getPatient()->getNric(),
                    'phone' => $consultation->getPatient()->getPhone(),
                ],
                'doctor' => [
                    'id' => $consultation->getDoctor()->getId(),
                    'name' => $consultation->getDoctor()->getName(),
                ],
                'consultation' => [
                    'id' => $consultation->getId(),
                    'consultation_fee' => $consultation->getConsultationFee(),
                    'medicines_fee' => $consultation->getMedicinesFee(),
                ],
                'medicines' => $medicines,
                'medicines_count' => count($medicines),
                'medicines_summary' => count($medicines) > 0 
                    ? implode(', ', array_slice(array_column($medicines, 'name'), 0, 3)) 
                    . (count($medicines) > 3 ? ' (+' . (count($medicines) - 3) . ' more)' : '')
                    : 'No medicines'
            ];
        }, $consultations);
    }



    #[Route('/export', name: 'api_financial_export', methods: ['GET'])]
    public function export(Request $request): JsonResponse
    {
        try {
            $period = $request->query->get('period', 'today');
            $startDate = $this->getStartDate($period);
            $endDate = new \DateTime('now');

            // Get detailed financial data
            $payments = $this->paymentRepository->findBy(
                ['paymentDate' => ['$gte' => $startDate, '$lte' => $endDate]],
                ['paymentDate' => 'DESC']
            );

            $exportData = [];
            foreach ($payments as $payment) {
                $exportData[] = [
                    'Date' => $payment->getPaymentDate()->format('Y-m-d H:i:s'),
                    'Patient' => $payment->getConsultation()->getPatient()->getName(),
                    'Doctor' => $payment->getConsultation()->getDoctor()->getName(),
                    'Amount' => $payment->getAmount(),
                    'Payment Method' => $payment->getPaymentMethod(),
                    'Reference' => $payment->getReference() ?? '',
                    'Notes' => $payment->getNotes() ?? ''
                ];
            }

            // For now, return JSON data - in a real application, you'd generate Excel/CSV
            return new JsonResponse([
                'success' => true,
                'data' => $exportData,
                'period' => $period,
                'total_records' => count($exportData)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    private function getStartDate(string $period): \DateTime
    {
        return match ($period) {
            'today' => \App\Service\TimezoneService::startOfDay(),
            'week' => \App\Service\TimezoneService::createDateTime('-1 week'),
            'month' => \App\Service\TimezoneService::createDateTime('-1 month'),
            'quarter' => \App\Service\TimezoneService::createDateTime('-3 months'),
            'year' => \App\Service\TimezoneService::createDateTime('-1 year'),
            'all' => \App\Service\TimezoneService::createDateTime('2020-01-01'), // Start from a reasonable date
            default => \App\Service\TimezoneService::startOfDay(),
        };
    }

    private function getPreviousPeriodStats(string $period): array
    {
        $previousStartDate = match ($period) {
            'today' => new \DateTime('yesterday'),
            'week' => new \DateTime('-2 weeks'),
            'month' => new \DateTime('-2 months'),
            'quarter' => new \DateTime('-6 months'),
            'year' => new \DateTime('-2 years'),
            'all' => new \DateTime('2019-01-01'),
            default => new \DateTime('yesterday'),
        };

        $previousEndDate = match ($period) {
            'today' => new \DateTime('yesterday 23:59:59'),
            'week' => new \DateTime('-1 week'),
            'month' => new \DateTime('-1 month'),
            'quarter' => new \DateTime('-3 months'),
            'year' => new \DateTime('-1 year'),
            'all' => new \DateTime('2020-01-01'),
            default => new \DateTime('yesterday 23:59:59'),
        };

        return $this->getDailyStats($previousStartDate, $previousEndDate);
    }

    private function calculateTrends(array $currentStats, array $previousStats): array
    {
        $trends = [];

        // Revenue change
        $currentRevenue = $currentStats['total_amount'];
        $previousRevenue = $previousStats['total_amount'];
        $trends['revenue_change'] = $previousRevenue > 0 
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : 0;

        // Consultations change
        $currentConsultations = $currentStats['total_consultations'];
        $previousConsultations = $previousStats['total_consultations'];
        $trends['consultations_change'] = $previousConsultations > 0 
            ? round((($currentConsultations - $previousConsultations) / $previousConsultations) * 100, 2)
            : 0;

        // Average per consultation change
        $currentAverage = $currentStats['total_consultations'] > 0 
            ? $currentStats['total_amount'] / $currentStats['total_consultations'] 
            : 0;
        $previousAverage = $previousStats['total_consultations'] > 0 
            ? $previousStats['total_amount'] / $previousStats['total_consultations'] 
            : 0;
        $trends['average_change'] = $previousAverage > 0 
            ? round((($currentAverage - $previousAverage) / $previousAverage) * 100, 2)
            : 0;

        return $trends;
    }

    private function getDailyStats(\DateTime $startDate, \DateTime $endDate): array
    {
        // Convert to UTC timezone for database comparison
        $startDateUtc = \App\Service\TimezoneService::convertToUtc($startDate);
        $endDateUtc = \App\Service\TimezoneService::convertToUtc($endDate);
        
        // Get payment stats using direct datetime comparison
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->select('COUNT(p.id) as total_transactions, SUM(p.amount) as total_amount')
            ->where('p.paymentDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateUtc)
            ->setParameter('endDate', $endDateUtc);

        $result = $qb->getQuery()->getSingleResult();

        // Get consultation stats using consultation date
        $consultationQb = $this->consultationRepository->createQueryBuilder('c')
            ->select('COUNT(c.id) as total_consultations')
            ->where('c.consultationDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        $consultationResult = $consultationQb->getQuery()->getSingleResult();

        return [
            'total_transactions' => (int) $result['total_transactions'],
            'total_amount' => (float) ($result['total_amount'] ?? 0),
            'total_consultations' => (int) $consultationResult['total_consultations'],
        ];
    }

    private function getMonthlyStats(): array
    {
        $currentMonth = new \DateTime('first day of this month');
        $lastMonth = new \DateTime('first day of last month');

        $currentStats = $this->getDailyStats($currentMonth, new \DateTime('now'));
        $lastMonthStats = $this->getDailyStats($lastMonth, new \DateTime('last day of last month'));

        return [
            'current_month' => $currentStats,
            'last_month' => $lastMonthStats,
            'growth_percentage' => $lastMonthStats['total_amount'] > 0 
                ? round((($currentStats['total_amount'] - $lastMonthStats['total_amount']) / $lastMonthStats['total_amount']) * 100, 2)
                : 0
        ];
    }

    private function getPaymentMethodStats(\DateTime $startDate, \DateTime $endDate): array
    {
        // Convert to UTC timezone for database comparison
        $startDateUtc = \App\Service\TimezoneService::convertToUtc($startDate);
        $endDateUtc = \App\Service\TimezoneService::convertToUtc($endDate);
        
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->select('p.paymentMethod, COUNT(p.id) as count, SUM(p.amount) as total')
            ->where('p.paymentDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateUtc)
            ->setParameter('endDate', $endDateUtc)
            ->groupBy('p.paymentMethod')
            ->orderBy('total', 'DESC');

        return $qb->getQuery()->getResult();
    }

    private function getRecentTransactions(int $limit): array
    {
        $payments = $this->paymentRepository->findBy([], ['paymentDate' => 'DESC'], $limit);

        return array_map(function ($payment) {
            return [
                'id' => $payment->getId(),
                'amount' => $payment->getAmount(),
                'payment_method' => $payment->getPaymentMethod(),
                'payment_date' => $payment->getPaymentDate()->format('Y-m-d H:i:s'),
                'patient_name' => $payment->getConsultation()->getPatient()->getName(),
            ];
        }, $payments);
    }

    private function getRevenueChartData(string $period): array
    {
        $endDate = new \DateTime('now');
        $startDate = match ($period) {
            'week' => new \DateTime('-1 week'),
            'month' => new \DateTime('-1 month'),
            'quarter' => new \DateTime('-3 months'),
            'year' => new \DateTime('-1 year'),
            default => new \DateTime('-1 month'),
        };

        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->select('DATE(p.paymentDate) as payment_date, SUM(p.amount) as daily_total')
            ->where('p.paymentDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('payment_date')
            ->orderBy('payment_date', 'ASC');

        return $qb->getQuery()->getResult();
    }

    private function getMedicationStats(\DateTime $startDate, \DateTime $endDate): array
    {
        // First try to get from PrescribedMedication entities
        $prescribedStats = $this->prescribedMedicationRepository->getMedicationUsageStats($startDate, $endDate);
        
        // If we have prescribed medication data, return it
        if (!empty($prescribedStats)) {
            return $prescribedStats;
        }
        
        // Otherwise, try to extract from consultation medications JSON
        $consultations = $this->consultationRepository->createQueryBuilder('c')
            ->where('c.consultationDate BETWEEN :startDate AND :endDate')
            ->andWhere('c.medications IS NOT NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
        
        $medicationCounts = [];
        
        foreach ($consultations as $consultation) {
            $medications = $consultation->getMedications();
            if ($medications) {
                // Try to parse as JSON
                $decoded = json_decode($medications, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    foreach ($decoded as $med) {
                        $name = $med['name'] ?? $med['medication'] ?? 'Unknown';
                        $quantity = (int) ($med['quantity'] ?? 1);
                        
                        if (!isset($medicationCounts[$name])) {
                            $medicationCounts[$name] = [
                                'medicationName' => $name,
                                'usageCount' => 0,
                                'totalQuantity' => 0
                            ];
                        }
                        
                        $medicationCounts[$name]['usageCount']++;
                        $medicationCounts[$name]['totalQuantity'] += $quantity;
                    }
                }
            }
        }
        
        // Sort by usage count and return top medications
        $stats = array_values($medicationCounts);
        usort($stats, function($a, $b) {
            return $b['usageCount'] - $a['usageCount'];
        });
        
        return array_slice($stats, 0, 10); // Return top 10
    }
} 