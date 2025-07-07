<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Psr\Log\LoggerInterface;
use App\Repository\SettingRepository;

class BackupService
{
    private Connection $connection;
    private Filesystem $filesystem;
    private LoggerInterface $logger;
    private SettingRepository $settingRepository;
    private string $projectDir;
    private string $backupDir;

    public function __construct(
        Connection $connection,
        Filesystem $filesystem,
        LoggerInterface $logger,
        SettingRepository $settingRepository,
        string $projectDir
    ) {
        $this->connection = $connection;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->settingRepository = $settingRepository;
        $this->projectDir = $projectDir;
        $this->backupDir = $projectDir . '/var/backups';
        
        // Ensure backup directory exists
        if (!$this->filesystem->exists($this->backupDir)) {
            $this->filesystem->mkdir($this->backupDir, 0755);
        }
    }

    /**
     * Create a complete system backup (database + files)
     */
    public function createFullBackup(): array
    {
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "full_backup_{$timestamp}";
        $backupPath = $this->backupDir . '/' . $backupName;
        
        try {
            // Create backup directory
            $this->filesystem->mkdir($backupPath, 0755);
            
            $results = [
                'backup_name' => $backupName,
                'backup_path' => $backupPath,
                'timestamp' => $timestamp,
                'database' => null,
                'files' => null,
                'success' => false
            ];
            
            // 1. Database backup
            $dbBackupResult = $this->createDatabaseBackup($backupPath);
            $results['database'] = $dbBackupResult;
            
            // 2. Files backup
            $filesBackupResult = $this->createFilesBackup($backupPath);
            $results['files'] = $filesBackupResult;
            
            // 3. Create backup manifest
            $this->createBackupManifest($backupPath, $results);
            
            // 4. Compress backup
            $compressResult = $this->compressBackup($backupPath);
            $results['compressed'] = $compressResult;
            
            $results['success'] = $dbBackupResult['success'] && $filesBackupResult['success'];
            
            if ($results['success']) {
                $this->logger->info('Full backup created successfully', $results);
            } else {
                $this->logger->error('Full backup failed', $results);
            }
            
            return $results;
            
        } catch (\Exception $e) {
            $this->logger->error('Backup creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_name' => $backupName
            ];
        }
    }

    /**
     * Create database backup using mysqldump
     */
    public function createDatabaseBackup(string $backupPath): array
    {
        try {
            $dbParams = $this->connection->getParams();
            $dbName = $dbParams['dbname'];
            $dbHost = $dbParams['host'] ?? 'localhost';
            $dbPort = $dbParams['port'] ?? 3306;
            $dbUser = $dbParams['user'];
            $dbPassword = $dbParams['password'];
            
            $sqlFile = $backupPath . '/database.sql';
            
            // Use mysqldump command
            $command = [
                'mysqldump',
                '--host=' . $dbHost,
                '--port=' . $dbPort,
                '--user=' . $dbUser,
                '--password=' . $dbPassword,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--add-drop-table',
                '--extended-insert',
                '--quick',
                '--lock-tables=false',
                $dbName
            ];
            
            $process = new Process($command);
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();
            
            if ($process->isSuccessful()) {
                file_put_contents($sqlFile, $process->getOutput());
                
                return [
                    'success' => true,
                    'file' => $sqlFile,
                    'size' => filesize($sqlFile),
                    'tables_count' => $this->getTablesCount()
                ];
            } else {
                throw new \Exception('mysqldump failed: ' . $process->getErrorOutput());
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Database backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create files backup (data files only - source code is in GitLab)
     */
    public function createFilesBackup(string $backupPath): array
    {
        try {
            $filesToBackup = [
                'public/uploads' => 'uploads',
                '.env' => '.env'
            ];
            
            $filesBackupPath = $backupPath . '/files';
            $this->filesystem->mkdir($filesBackupPath, 0755);
            
            $backedUpFiles = [];
            $totalSize = 0;
            
            foreach ($filesToBackup as $source => $destination) {
                $sourcePath = $this->projectDir . '/' . $source;
                $destPath = $filesBackupPath . '/' . $destination;
                
                if ($this->filesystem->exists($sourcePath)) {
                    if (is_file($sourcePath)) {
                        $this->filesystem->copy($sourcePath, $destPath);
                        $size = filesize($sourcePath);
                    } else {
                        $this->filesystem->mirror($sourcePath, $destPath);
                        $size = $this->getDirectorySize($sourcePath);
                    }
                    
                    $backedUpFiles[] = [
                        'source' => $source,
                        'destination' => $destination,
                        'size' => $size
                    ];
                    $totalSize += $size;
                }
            }
            
            return [
                'success' => true,
                'files' => $backedUpFiles,
                'total_size' => $totalSize,
                'files_count' => count($backedUpFiles)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Files backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create backup manifest with metadata
     */
    private function createBackupManifest(string $backupPath, array $backupData): void
    {
        $manifest = [
            'backup_info' => [
                'created_at' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'type' => 'full_backup',
                'php_version' => PHP_VERSION,
                'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION
            ],
            'database' => $backupData['database'],
            'files' => $backupData['files'],
            'system_info' => [
                'os' => PHP_OS,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];
        
        file_put_contents(
            $backupPath . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Compress backup directory
     */
    private function compressBackup(string $backupPath): array
    {
        try {
            $zipFile = $backupPath . '.zip';
            $zip = new \ZipArchive();
            
            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                $this->addDirectoryToZip($zip, $backupPath, '');
                $zip->close();
                
                // Remove uncompressed directory
                $this->filesystem->remove($backupPath);
                
                return [
                    'success' => true,
                    'compressed_file' => $zipFile,
                    'size' => filesize($zipFile)
                ];
            } else {
                throw new \Exception('Failed to create zip file');
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add directory to zip recursively
     */
    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $zipPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPath . substr($filePath, strlen($dir) + 1);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * List available backups
     */
    public function listBackups(): array
    {
        $backups = [];
        $backupFiles = glob($this->backupDir . '/full_backup_*.zip');
        
        foreach ($backupFiles as $backupFile) {
            $filename = basename($backupFile);
            $backups[] = [
                'filename' => $filename,
                'path' => $backupFile,
                'size' => filesize($backupFile),
                'created_at' => date('Y-m-d H:i:s', filemtime($backupFile)),
                'age_days' => floor((time() - filemtime($backupFile)) / 86400)
            ];
        }
        
        // Sort by creation time (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b['path']) - filemtime($a['path']);
        });
        
        return $backups;
    }

    /**
     * Check if automatic backups are enabled
     */
    public function isBackupEnabled(): bool
    {
        return $this->settingRepository->getSettingValue('system.backup_enabled', true);
    }

    /**
     * Get backup time from settings
     */
    public function getBackupTime(): string
    {
        return $this->settingRepository->getSettingValue('system.backup_time', '18:30');
    }

    /**
     * Get backup frequency from settings
     */
    public function getBackupFrequency(): string
    {
        return $this->settingRepository->getSettingValue('system.backup_frequency', 'daily');
    }

    /**
     * Get backup retention settings
     */
    public function getBackupRetentionSettings(): array
    {
        return [
            'days' => $this->settingRepository->getSettingValue('system.backup_retention_days', 30),
            'count' => $this->settingRepository->getSettingValue('system.backup_retention_count', 10)
        ];
    }

    /**
     * Clean old backups based on settings
     */
    public function cleanOldBackupsBasedOnSettings(): array
    {
        $retention = $this->getBackupRetentionSettings();
        return $this->cleanOldBackups($retention['count']);
    }

    /**
     * Clean old backups (keep only specified number)
     */
    public function cleanOldBackups(int $keepCount = 10): array
    {
        $backups = $this->listBackups();
        $deleted = [];
        
        if (count($backups) > $keepCount) {
            $toDelete = array_slice($backups, $keepCount);
            
            foreach ($toDelete as $backup) {
                if ($this->filesystem->exists($backup['path'])) {
                    $this->filesystem->remove($backup['path']);
                    $deleted[] = $backup['filename'];
                }
            }
        }
        
        return [
            'deleted_count' => count($deleted),
            'deleted_files' => $deleted,
            'remaining_count' => count($backups) - count($deleted)
        ];
    }

    /**
     * Get database tables count
     */
    private function getTablesCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()";
        $result = $this->connection->executeQuery($sql);
        return $result->fetchOne();
    }

    /**
     * Get directory size recursively
     */
    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }

    /**
     * Restore from backup
     */
    public function restoreFromBackup(string $backupFile): array
    {
        try {
            if (!$this->filesystem->exists($backupFile)) {
                throw new \Exception('Backup file not found: ' . $backupFile);
            }
            
            $restoreDir = $this->backupDir . '/restore_' . time();
            $this->filesystem->mkdir($restoreDir, 0755);
            
            // Extract backup
            $zip = new \ZipArchive();
            if ($zip->open($backupFile) === TRUE) {
                $zip->extractTo($restoreDir);
                $zip->close();
            } else {
                throw new \Exception('Failed to extract backup file');
            }
            
            // Restore database
            $dbRestoreResult = $this->restoreDatabase($restoreDir . '/database.sql');
            
            // Restore files (optional - usually manual process)
            $filesRestoreResult = ['success' => true, 'message' => 'Files restore skipped (manual process recommended)'];
            
            // Clean up
            $this->filesystem->remove($restoreDir);
            
            return [
                'success' => $dbRestoreResult['success'],
                'database' => $dbRestoreResult,
                'files' => $filesRestoreResult
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Restore failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Restore database from SQL file
     */
    private function restoreDatabase(string $sqlFile): array
    {
        try {
            if (!$this->filesystem->exists($sqlFile)) {
                throw new \Exception('SQL file not found: ' . $sqlFile);
            }
            
            $dbParams = $this->connection->getParams();
            $dbName = $dbParams['dbname'];
            $dbHost = $dbParams['host'] ?? 'localhost';
            $dbPort = $dbParams['port'] ?? 3306;
            $dbUser = $dbParams['user'];
            $dbPassword = $dbParams['password'];
            
            $command = [
                'mysql',
                '--host=' . $dbHost,
                '--port=' . $dbPort,
                '--user=' . $dbUser,
                '--password=' . $dbPassword,
                $dbName
            ];
            
            $process = new Process($command);
            $process->setInput(file_get_contents($sqlFile));
            $process->setTimeout(300);
            $process->run();
            
            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'message' => 'Database restored successfully'
                ];
            } else {
                throw new \Exception('Database restore failed: ' . $process->getErrorOutput());
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 
