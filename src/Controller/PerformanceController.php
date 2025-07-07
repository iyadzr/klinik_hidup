<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/performance')]
class PerformanceController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/health', name: 'api_performance_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $startTime = microtime(true);
        
        $checks = [
            'database' => $this->checkDatabase(),
            'memory' => $this->checkMemory(),
            'disk' => $this->checkDisk(),
            'queries' => $this->checkQueryPerformance(),
            'connections' => $this->checkDatabaseConnections()
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
            'checks' => $checks,
            'recommendations' => $this->getPerformanceRecommendations($checks)
        ]);
    }

    #[Route('/metrics', name: 'api_performance_metrics', methods: ['GET'])]
    public function metrics(): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $metrics = [
                'database' => $this->getDatabaseMetrics(),
                'api' => $this->getApiMetrics(),
                'system' => $this->getSystemMetrics(),
                'query_analysis' => $this->getSlowQueryAnalysis()
            ];
            
            $executionTime = microtime(true) - $startTime;
            
            return new JsonResponse([
                'metrics' => $metrics,
                'collection_time' => round($executionTime * 1000, 2) . 'ms',
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error collecting performance metrics: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to collect metrics'], 500);
        }
    }

    #[Route('/optimize', name: 'api_performance_optimize', methods: ['POST'])]
    public function optimize(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $optimizations = [];
            
            // Optimize database tables
            $optimizations['table_optimization'] = $this->optimizeTables();
            
            // Clear query cache if needed
            $optimizations['cache_optimization'] = $this->optimizeCache();
            
            // Analyze slow queries
            $optimizations['query_optimization'] = $this->analyzeSlowQueries();
            
            $executionTime = microtime(true) - $startTime;
            
            return new JsonResponse([
                'status' => 'completed',
                'optimizations' => $optimizations,
                'execution_time' => round($executionTime * 1000, 2) . 'ms',
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error during optimization: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Optimization failed'], 500);
        }
    }

    private function checkDatabase(): array
    {
        $basicDuration = 0;
        $complexDuration = 0;
        
        try {
            $connection = $this->entityManager->getConnection();
            
            // Test basic connectivity
            $startTime = microtime(true);
            $result = $connection->executeQuery('SELECT 1')->fetchOne();
            $basicDuration = microtime(true) - $startTime;
            
            // Test a more complex query (queue list simulation)
            $startTime = microtime(true);
            $connection->executeQuery('SELECT COUNT(*) FROM queue WHERE queue_date_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)')->fetchOne();
            $complexDuration = microtime(true) - $startTime;
            
            $status = 'healthy';
            $warnings = [];
            
            if ($basicDuration > 0.5) {
                $status = 'degraded';
                $warnings[] = 'Basic database queries are slow';
            }
            if ($complexDuration > 2.0) {
                $status = 'unhealthy';
                $warnings[] = 'Complex queries are timing out';
            }
            if ($basicDuration > 2.0) {
                $status = 'unhealthy';
                $warnings[] = 'Database connection is severely degraded';
            }
            
            return [
                'status' => $status,
                'basic_query_time' => round($basicDuration * 1000, 2) . 'ms',
                'complex_query_time' => round($complexDuration * 1000, 2) . 'ms',
                'warnings' => $warnings
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'basic_query_time' => round($basicDuration * 1000, 2) . 'ms',
                'complex_query_time' => round($complexDuration * 1000, 2) . 'ms'
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
        $warnings = [];
        
        if ($usagePercent > 70) {
            $status = 'degraded';
            $warnings[] = 'Memory usage is high';
        }
        if ($usagePercent > 90) {
            $status = 'unhealthy';
            $warnings[] = 'Memory usage is critical';
        }
        
        return [
            'status' => $status,
            'usage_percent' => round($usagePercent, 2),
            'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'limit_mb' => round($memoryLimitBytes / 1024 / 1024, 2),
            'warnings' => $warnings
        ];
    }

    private function checkDisk(): array
    {
        try {
            $totalSpace = disk_total_space('/');
            $freeSpace = disk_free_space('/');
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = ($usedSpace / $totalSpace) * 100;
            
            $status = 'healthy';
            $warnings = [];
            
            if ($usagePercent > 80) {
                $status = 'degraded';
                $warnings[] = 'Disk usage is high';
            }
            if ($usagePercent > 95) {
                $status = 'unhealthy';
                $warnings[] = 'Disk usage is critical';
            }
            
            return [
                'status' => $status,
                'usage_percent' => round($usagePercent, 2),
                'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'warnings' => $warnings
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkQueryPerformance(): array
    {
        try {
            $connection = $this->entityManager->getConnection();
            
            // Check for slow queries
            $slowQueries = $connection->executeQuery('SHOW GLOBAL STATUS LIKE "Slow_queries"')->fetchAllAssociative();
            $totalQueries = $connection->executeQuery('SHOW GLOBAL STATUS LIKE "Questions"')->fetchAllAssociative();
            
            $slowQueryCount = $slowQueries[0]['Value'] ?? 0;
            $totalQueryCount = $totalQueries[0]['Value'] ?? 1;
            $slowQueryPercent = ($slowQueryCount / $totalQueryCount) * 100;
            
            $status = 'healthy';
            $warnings = [];
            
            if ($slowQueryPercent > 1) {
                $status = 'degraded';
                $warnings[] = 'High percentage of slow queries';
            }
            if ($slowQueryPercent > 5) {
                $status = 'unhealthy';
                $warnings[] = 'Critical percentage of slow queries';
            }
            
            return [
                'status' => $status,
                'slow_queries' => $slowQueryCount,
                'total_queries' => $totalQueryCount,
                'slow_query_percent' => round($slowQueryPercent, 2),
                'warnings' => $warnings
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkDatabaseConnections(): array
    {
        try {
            $connection = $this->entityManager->getConnection();
            
            $processlist = $connection->executeQuery('SHOW PROCESSLIST')->fetchAllAssociative();
            $activeConnections = count($processlist);
            
            $maxConnections = $connection->executeQuery('SHOW VARIABLES LIKE "max_connections"')->fetchAssociative();
            $maxConnectionsValue = $maxConnections['Value'] ?? 200;
            
            $connectionPercent = ($activeConnections / $maxConnectionsValue) * 100;
            
            $status = 'healthy';
            $warnings = [];
            
            if ($connectionPercent > 70) {
                $status = 'degraded';
                $warnings[] = 'High database connection usage';
            }
            if ($connectionPercent > 90) {
                $status = 'unhealthy';
                $warnings[] = 'Critical database connection usage';
            }
            
            return [
                'status' => $status,
                'active_connections' => $activeConnections,
                'max_connections' => $maxConnectionsValue,
                'connection_percent' => round($connectionPercent, 2),
                'warnings' => $warnings
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    private function getDatabaseMetrics(): array
    {
        try {
            $connection = $this->entityManager->getConnection();
            
            // Get table sizes
            $tableSizes = $connection->executeQuery('
                SELECT table_name, 
                       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ')->fetchAllAssociative();
            
            // Get index usage
            $indexUsage = $connection->executeQuery('
                SELECT table_name, index_name, cardinality
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE() AND index_name != "PRIMARY"
                ORDER BY cardinality DESC
            ')->fetchAllAssociative();
            
            return [
                'table_sizes' => $tableSizes,
                'index_usage' => array_slice($indexUsage, 0, 10) // Top 10 indexes
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getApiMetrics(): array
    {
        // This would typically come from application metrics storage
        // For now, we'll return basic PHP metrics
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
            'current_memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
        ];
    }

    private function getSystemMetrics(): array
    {
        return [
            'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
            'disk_free_space' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . 'GB',
            'uptime' => $this->getSystemUptime()
        ];
    }

    private function getSlowQueryAnalysis(): array
    {
        try {
            $connection = $this->entityManager->getConnection();
            
            // Get slow query log status
            $slowQueryLog = $connection->executeQuery('SHOW VARIABLES LIKE "slow_query_log"')->fetchAssociative();
            $longQueryTime = $connection->executeQuery('SHOW VARIABLES LIKE "long_query_time"')->fetchAssociative();
            
            return [
                'slow_query_log_enabled' => $slowQueryLog['Value'] === 'ON',
                'long_query_time_seconds' => $longQueryTime['Value'] ?? 'unknown',
                'recommendation' => 'Monitor slow queries regularly to identify performance bottlenecks'
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function optimizeTables(): array
    {
        try {
            $connection = $this->entityManager->getConnection();
            
            $tables = ['queue', 'patient', 'consultation', 'doctor', 'payment'];
            $results = [];
            
            foreach ($tables as $table) {
                $result = $connection->executeQuery("OPTIMIZE TABLE {$table}")->fetchAssociative();
                $results[$table] = $result['Msg_text'] ?? 'optimized';
            }
            
            return [
                'status' => 'completed',
                'tables' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function optimizeCache(): array
    {
        try {
            $connection = $this->entityManager->getConnection();
            
            // Reset query cache
            $connection->executeStatement('RESET QUERY CACHE');
            
            return [
                'status' => 'completed',
                'action' => 'Query cache reset'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function analyzeSlowQueries(): array
    {
        // This would analyze the slow query log
        // For now, return basic recommendations
        return [
            'status' => 'analyzed',
            'recommendations' => [
                'Add indexes to frequently queried columns',
                'Optimize JOIN operations',
                'Use LIMIT clauses for large result sets',
                'Consider query caching for repeated queries'
            ]
        ];
    }

    private function getPerformanceRecommendations(array $checks): array
    {
        $recommendations = [];
        
        foreach ($checks as $checkName => $check) {
            if ($check['status'] === 'degraded' || $check['status'] === 'unhealthy') {
                switch ($checkName) {
                    case 'database':
                        $recommendations[] = 'Consider adding database indexes or optimizing queries';
                        break;
                    case 'memory':
                        $recommendations[] = 'Increase PHP memory limit or optimize memory usage';
                        break;
                    case 'disk':
                        $recommendations[] = 'Free up disk space or expand storage';
                        break;
                    case 'queries':
                        $recommendations[] = 'Optimize slow queries and add appropriate indexes';
                        break;
                    case 'connections':
                        $recommendations[] = 'Optimize database connection usage or increase max connections';
                        break;
                }
            }
        }
        
        return $recommendations;
    }

    private function convertToBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    private function getSystemUptime(): ?string
    {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime');
            return $uptime ? trim($uptime) : null;
        }
        return null;
    }
} 