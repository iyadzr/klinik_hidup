<?php

namespace App\Controller;

use App\Service\BackupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Process\Process;

#[Route('/api/backup')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class BackupController extends AbstractController
{
    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    #[Route('/create', name: 'api_backup_create', methods: ['POST'])]
    public function createBackup(): JsonResponse
    {
        try {
            $result = $this->backupService->createFullBackup();
            
            if ($result['success']) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'data' => $result
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Backup creation failed',
                    'error' => $result['error'] ?? 'Unknown error'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Backup creation failed',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list', name: 'api_backup_list', methods: ['GET'])]
    public function listBackups(): JsonResponse
    {
        try {
            $backups = $this->backupService->listBackups();
            
            return new JsonResponse([
                'success' => true,
                'data' => $backups,
                'count' => count($backups)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to list backups',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/download/{filename}', name: 'api_backup_download', methods: ['GET'])]
    public function downloadBackup(string $filename): Response
    {
        try {
            $backups = $this->backupService->listBackups();
            $backup = null;
            
            foreach ($backups as $b) {
                if ($b['filename'] === $filename) {
                    $backup = $b;
                    break;
                }
            }
            
            if (!$backup) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $response = new BinaryFileResponse($backup['path']);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );
            
            return $response;
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to download backup',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/restore', name: 'api_backup_restore', methods: ['POST'])]
    public function restoreBackup(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $filename = $data['filename'] ?? null;
            
            if (!$filename) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Filename is required'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $backups = $this->backupService->listBackups();
            $backupPath = null;
            
            foreach ($backups as $backup) {
                if ($backup['filename'] === $filename) {
                    $backupPath = $backup['path'];
                    break;
                }
            }
            
            if (!$backupPath) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $result = $this->backupService->restoreFromBackup($backupPath);
            
            if ($result['success']) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Backup restored successfully',
                    'data' => $result
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Backup restoration failed',
                    'error' => $result['error'] ?? 'Unknown error'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Backup restoration failed',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/clean', name: 'api_backup_clean', methods: ['POST'])]
    public function cleanOldBackups(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $keepCount = $data['keep_count'] ?? 10;
            
            $result = $this->backupService->cleanOldBackups($keepCount);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Old backups cleaned successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to clean old backups',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/status', name: 'api_backup_status', methods: ['GET'])]
    public function getBackupStatus(): JsonResponse
    {
        try {
            $backups = $this->backupService->listBackups();
            $totalSize = array_sum(array_column($backups, 'size'));
            
            $status = [
                'total_backups' => count($backups),
                'total_size' => $totalSize,
                'total_size_formatted' => $this->formatBytes($totalSize),
                'latest_backup' => $backups[0] ?? null,
                'oldest_backup' => end($backups) ?: null,
                'backup_directory' => '/var/backups',
                'disk_space_available' => disk_free_space(dirname(__DIR__, 2) . '/var/backups'),
                'disk_space_total' => disk_total_space(dirname(__DIR__, 2) . '/var/backups')
            ];
            
            return new JsonResponse([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to get backup status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/update-schedule', name: 'api_backup_update_schedule', methods: ['POST'])]
    public function updateBackupSchedule(): JsonResponse
    {
        try {
            // Get project directory
            $projectDir = dirname(__DIR__, 2);
            
            // Run the backup schedule command
            $process = new Process([
                'php',
                $projectDir . '/bin/console',
                'app:backup:schedule',
                '--no-interaction'
            ]);
            
            $process->setWorkingDirectory($projectDir);
            $process->setTimeout(60);
            $process->run();
            
            if ($process->isSuccessful()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Backup schedule updated successfully',
                    'output' => $process->getOutput()
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update backup schedule',
                    'error' => $process->getErrorOutput()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to update backup schedule',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
} 