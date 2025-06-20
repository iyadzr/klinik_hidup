<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/performance')]
class PerformanceController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/health', name: 'api_performance_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $startTime = microtime(true);
        
        $checks = [
            'database' => $this->checkDatabase(),
            'memory' => $this->checkMemory(),
            'disk' => $this->checkDisk()
        ];
        
        $overallHealth = 'healthy';
        foreach ($checks as $check) {
            if ($check['status'] !== 'healthy') {
                $overallHealth = 'degraded';
                if ($check['status'] === 'unhealthy') {
                    $overallHealth = 'unhealthy';
                    break;
                }
            }
        }
        
        $duration = microtime(true) - $startTime;
        
        return new JsonResponse([
            'status' => $overallHealth,
            'timestamp' => date('c'),
            'response_time' => round($duration * 1000, 2) . 'ms',
            'checks' => $checks
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            $connection = $this->entityManager->getConnection();
            $result = $connection->executeQuery('SELECT 1')->fetchOne();
            $duration = microtime(true) - $startTime;
            
            $status = 'healthy';
            if ($duration > 1.0) {
                $status = 'degraded';
            }
            if ($duration > 5.0) {
                $status = 'unhealthy';
            }
            
            return [
                'status' => $status,
                'response_time' => round($duration * 1000, 2) . 'ms'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkMemory(): array
    {
        $memoryUsage = memory_get_usage();
        $memoryLimit = ini_get('memory_limit');
        
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $usagePercent = $memoryLimitBytes > 0 ? ($memoryUsage / $memoryLimitBytes) * 100 : 0;
        
        $status = 'healthy';
        if ($usagePercent > 70) {
            $status = 'degraded';
        }
        if ($usagePercent > 90) {
            $status = 'unhealthy';
        }
        
        return [
            'status' => $status,
            'usage_percent' => round($usagePercent, 2)
        ];
    }

    private function checkDisk(): array
    {
        $path = __DIR__ . '/../../';
        $freeBytes = disk_free_space($path);
        $totalBytes = disk_total_space($path);
        
        $usagePercent = $totalBytes > 0 ? (($totalBytes - $freeBytes) / $totalBytes) * 100 : 0;
        
        $status = 'healthy';
        if ($usagePercent > 80) {
            $status = 'degraded';
        }
        if ($usagePercent > 95) {
            $status = 'unhealthy';
        }
        
        return [
            'status' => $status,
            'usage_percent' => round($usagePercent, 2)
        ];
    }

    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;
        
        switch ($last) {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }
        
        return $number;
    }
} 