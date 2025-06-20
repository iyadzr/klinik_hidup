<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/sse')]
class SSEController extends AbstractController
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/queue-updates', name: 'app_sse_queue_updates', methods: ['GET'])]
    public function queueUpdates(): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Cache-Control');
        $response->headers->set('X-Accel-Buffering', 'no'); // For nginx
        
        $response->setCallback(function () {
            $startTime = time();
            $lastUpdateTime = 0;
            $lastHeartbeat = 0;
            $connectionId = uniqid('sse_', true);
            $maxConnectionTime = 3600; // 1 hour max connection time
            $heartbeatInterval = 15; // Reduced from 30 to 15 seconds
            $checkInterval = 1; // Reduced from 2 to 1 second for faster updates
            
            $tempDir = sys_get_temp_dir();
            $updateFile = $tempDir . '/queue_updates.json';
            
            // Log connection start
            $this->logger->info('SSE connection started', ['connection_id' => $connectionId]);
            
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
                    
                    // Check for updates
                    if (file_exists($updateFile)) {
                        clearstatcache(true, $updateFile); // Clear file stat cache
                        $fileTime = filemtime($updateFile);
                        
                        if ($fileTime > $lastUpdateTime) {
                            $content = file_get_contents($updateFile);
                            if ($content !== false) {
                                $updates = json_decode($content, true) ?: [];
                                
                                // Send only new updates
                                $sentUpdates = 0;
                                foreach ($updates as $update) {
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
                                    $this->logger->debug('SSE updates sent', [
                                        'connection_id' => $connectionId,
                                        'updates_sent' => $sentUpdates
                                    ]);
                                }
                                
                                $lastUpdateTime = $fileTime;
                            }
                        }
                    }
                    
                    // Send heartbeat to keep connection alive
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