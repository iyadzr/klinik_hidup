<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Queue;

#[Route('/api/sse')]
class SSEController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    
    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    #[Route('/queue-updates', name: 'app_sse_queue_updates', methods: ['GET'])]
    #[Route('/updates', name: 'app_sse_updates', methods: ['GET'])]
    public function queueUpdates(Request $request): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Cache-Control, Content-Type, Authorization');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('X-Accel-Buffering', 'no'); // For nginx
        
        $response->setCallback(function () use ($request) {
            // Optional token validation (for authenticated features)
            $token = $request->query->get('token');
            $isAuthenticated = false;
            
            if ($token) {
                try {
                    // Basic JWT validation (you might want to use a proper JWT library)
                    $parts = explode('.', $token);
                    if (count($parts) === 3) {
                        $payload = json_decode(base64_decode($parts[1]), true);
                        if ($payload && isset($payload['exp']) && $payload['exp'] > time()) {
                            $isAuthenticated = true;
                            $this->logger->info('SSE connection authenticated', ['user' => $payload['username'] ?? 'unknown']);
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('SSE token validation failed', ['error' => $e->getMessage()]);
                }
            }
            
            $startTime = time();
            $lastUpdateTime = 0;
            $lastHeartbeat = 0;
            $connectionId = uniqid('sse_', true);
            $maxConnectionTime = 1800; // Reduced from 3600 to 30 minutes
            $heartbeatInterval = 60; // Increased from 30 to 60 seconds to reduce server load
            $checkInterval = 5; // Increased from 2 to 5 seconds to prevent deadlocks
            
            $tempDir = sys_get_temp_dir();
            $updateFile = $tempDir . '/queue_updates.json';
            $paymentUpdateFile = $tempDir . '/payment_updates.json';
            
            // Log connection start
            $this->logger->info('SSE connection started', ['connection_id' => $connectionId]);
            
            // Send initial connection message with pending actions count
            $pendingActions = $this->getPendingActionsCount();
            
            echo "event: connected\n";
            echo "data: " . json_encode([
                'type' => 'connection_established',
                'timestamp' => $startTime,
                'connection_id' => $connectionId,
                'authenticated' => $isAuthenticated,
                'pendingActions' => $pendingActions
            ]) . "\n\n";
            
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
            
            try {
                while (true) {
                    $currentTime = time();
                    
                    // Check maximum connection time to prevent memory leaks
                    if (($currentTime - $startTime) > $maxConnectionTime) {
                        $this->logger->info('SSE connection closed - max time reached', ['connection_id' => $connectionId]);
                        break;
                    }
                    
                    // Check if client disconnected
                    if (connection_aborted()) {
                        $this->logger->info('SSE connection aborted by client', ['connection_id' => $connectionId]);
                        break;
                    }
                    
                    // Check for queue updates with better caching
                    if (file_exists($updateFile)) {
                        clearstatcache(true, $updateFile); // Clear file stat cache
                        $fileTime = filemtime($updateFile);
                        
                        // Only process if file was modified recently (within last 2 minutes) and avoid excessive checks
                        if ($fileTime > ($currentTime - 120) && $fileTime > $lastUpdateTime) {
                            $content = file_get_contents($updateFile);
                            if ($content !== false) {
                                $updates = json_decode($content, true) ?: [];
                                
                                // Send only new updates and limit to last 5 updates
                                $recentUpdates = array_slice($updates, -5);
                                $sentUpdates = 0;
                                
                                foreach ($recentUpdates as $update) {
                                    if (isset($update['timestamp']) && $update['timestamp'] > $lastUpdateTime) {
                                        echo "data: " . json_encode($update) . "\n\n";
                                        $sentUpdates++;
                                    }
                                }
                                
                                if ($sentUpdates > 0) {
                                    if (ob_get_level()) {
                                        ob_flush();
                                    }
                                    flush();
                                    $this->logger->debug('SSE queue updates sent', [
                                        'connection_id' => $connectionId,
                                        'updates_sent' => $sentUpdates
                                    ]);
                                }
                                
                                $lastUpdateTime = $fileTime;
                            }
                        }
                    }
                    
                    // Check for payment updates
                    if (file_exists($paymentUpdateFile)) {
                        clearstatcache(true, $paymentUpdateFile);
                        $paymentFileTime = filemtime($paymentUpdateFile);
                        
                        // Only process if file was modified recently (within last 2 minutes)
                        if ($paymentFileTime > ($currentTime - 120) && $paymentFileTime > $lastUpdateTime) {
                            $content = file_get_contents($paymentUpdateFile);
                            if ($content !== false) {
                                $paymentUpdates = json_decode($content, true) ?: [];
                                
                                // Send only new payment updates
                                $recentPaymentUpdates = array_slice($paymentUpdates, -5);
                                $sentPaymentUpdates = 0;
                                
                                foreach ($recentPaymentUpdates as $update) {
                                    if (isset($update['timestamp']) && $update['timestamp'] > $lastUpdateTime) {
                                        echo "data: " . json_encode($update) . "\n\n";
                                        $sentPaymentUpdates++;
                                    }
                                }
                                
                                if ($sentPaymentUpdates > 0) {
                                    if (ob_get_level()) {
                                        ob_flush();
                                    }
                                    flush();
                                    $this->logger->debug('SSE payment updates sent', [
                                        'connection_id' => $connectionId,
                                        'payment_updates_sent' => $sentPaymentUpdates
                                    ]);
                                }
                                
                                $lastUpdateTime = max($lastUpdateTime, $paymentFileTime);
                            }
                        }
                    }
                    
                    // Send heartbeat to keep connection alive (less frequent)
                    if (($currentTime - $lastHeartbeat) >= $heartbeatInterval) {
                        echo "event: heartbeat\n";
                        echo "data: " . json_encode([
                            'timestamp' => $currentTime,
                            'connection_id' => $connectionId,
                            'uptime' => $currentTime - $startTime
                        ]) . "\n\n";
                        
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                        
                        $lastHeartbeat = $currentTime;
                    }
                    
                    // Sleep to reduce CPU usage
                    sleep($checkInterval);
                }
            } catch (\Exception $e) {
                $this->logger->error('SSE connection error', [
                    'connection_id' => $connectionId,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            } finally {
                $this->logger->info('SSE connection ended', [
                    'connection_id' => $connectionId,
                    'duration' => time() - $startTime
                ]);
            }
        });
        
        return $response;
    }

    private function getPendingActionsCount(): int
    {
        try {
            $today = \App\Service\TimezoneService::now();
            $startOfDay = (clone $today)->setTime(0, 0, 0);
            $endOfDay = (clone $today)->setTime(23, 59, 59);
            
            // Count consultations that are completed but not paid (pending payment processing)
            $pendingPaymentCount = $this->entityManager->getRepository(Queue::class)
                ->createQueryBuilder('q')
                ->select('COUNT(q.id)')
                ->where('q.queueDateTime BETWEEN :start AND :end')
                ->andWhere('q.status = :status')
                ->andWhere('(q.isPaid = false OR q.isPaid IS NULL)')
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->setParameter('status', 'completed_consultation')
                ->getQuery()
                ->getSingleScalarResult();
            
            return (int) $pendingPaymentCount;
        } catch (\Exception $e) {
            $this->logger->error('Error getting pending actions count: ' . $e->getMessage());
            return 0;
        }
    }
    
    #[Route('/health', name: 'app_sse_health', methods: ['GET'])]
    public function health(): Response
    {
        $tempDir = sys_get_temp_dir();
        $updateFile = $tempDir . '/queue_updates.json';
        
        $status = [
            'status' => 'healthy',
            'timestamp' => time(),
            'update_file_exists' => file_exists($updateFile),
            'update_file_writable' => is_writable($tempDir),
            'temp_dir' => $tempDir
        ];
        
        if (file_exists($updateFile)) {
            $status['last_update'] = filemtime($updateFile);
            $status['file_size'] = filesize($updateFile);
        }
        
        return $this->json($status);
    }
} 