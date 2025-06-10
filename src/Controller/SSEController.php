<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/sse')]
class SSEController extends AbstractController
{
    #[Route('/queue-updates', name: 'app_sse_queue_updates', methods: ['GET'])]
    public function queueUpdates(): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Cache-Control');
        
        $response->setCallback(function () {
            $lastUpdateTime = 0;
            $tempDir = sys_get_temp_dir();
            $updateFile = $tempDir . '/queue_updates.json';
            
            while (true) {
                // Check if file exists and has been modified
                if (file_exists($updateFile)) {
                    $fileTime = filemtime($updateFile);
                    
                    if ($fileTime > $lastUpdateTime) {
                        $content = file_get_contents($updateFile);
                        $updates = json_decode($content, true) ?: [];
                        
                        // Send only new updates (those with timestamp > lastUpdateTime)
                        foreach ($updates as $update) {
                            if ($update['timestamp'] > $lastUpdateTime) {
                                echo "data: " . json_encode($update) . "\n\n";
                                flush();
                            }
                        }
                        
                        $lastUpdateTime = $fileTime;
                    }
                }
                
                // Send heartbeat every 30 seconds to keep connection alive
                echo "event: heartbeat\n";
                echo "data: " . json_encode(['timestamp' => time()]) . "\n\n";
                flush();
                
                // Sleep for 2 seconds before checking again
                sleep(2);
                
                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }
            }
        });
        
        return $response;
    }
} 