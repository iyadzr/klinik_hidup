<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\ConsultationRepository;
use App\Repository\PrescribedMedicationRepository;
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
        private PrescribedMedicationRepository $prescribedMedicationRepository
    ) {}

    #[Route('/dashboard', name: 'api_financial_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'today'); // today, week, month, quarter, year, all

        $startDate = $this->getStartDate($period);
        $endDate = new \DateTime('now');

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
        $limit = (int) $request->query->get('limit', 20);
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        $criteria = [];
        if ($startDate) {
            $criteria['start_date'] = new \DateTime($startDate);
        }
        if ($endDate) {
            $criteria['end_date'] = new \DateTime($endDate);
        }

        $payments = $this->paymentRepository->findPaginated($page, $limit, $criteria);
        $total = $this->paymentRepository->countFiltered($criteria);

        $data = array_map(function ($payment) {
            return [
                'id' => $payment->getId(),
                'amount' => $payment->getAmount(),
                'payment_method' => $payment->getPaymentMethod(),
                'payment_date' => $payment->getPaymentDate()->format('Y-m-d H:i:s'),
                'reference' => $payment->getReference(),
                'consultation' => [
                    'id' => $payment->getConsultation()->getId(),
                    'patient_name' => $payment->getConsultation()->getPatient()->getName(),
                    'doctor_name' => $payment->getConsultation()->getDoctor()->getName(),
                ],
            ];
        }, $payments);

        return new JsonResponse([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ]);
    }

    #[Route('/payments/summary', name: 'api_payments_summary', methods: ['GET'])]
    public function paymentsSummary(): JsonResponse
    {
        return new JsonResponse($this->paymentRepository->getSummary());
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
            'today' => new \DateTime('today'),
            'week' => new \DateTime('-1 week'),
            'month' => new \DateTime('-1 month'),
            'quarter' => new \DateTime('-3 months'),
            'year' => new \DateTime('-1 year'),
            'all' => new \DateTime('2020-01-01'), // Start from a reasonable date
            default => new \DateTime('today'),
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
        // Get payment stats
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->select('COUNT(p.id) as total_transactions, SUM(p.amount) as total_amount')
            ->where('p.paymentDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

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
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->select('p.paymentMethod, COUNT(p.id) as count, SUM(p.amount) as total')
            ->where('p.paymentDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
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