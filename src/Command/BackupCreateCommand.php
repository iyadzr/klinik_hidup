<?php

namespace App\Command;

use App\Service\BackupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backup:create',
    description: 'Create a full system backup (database + files)',
)]
class BackupCreateCommand extends Command
{
    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('clean', 'c', InputOption::VALUE_OPTIONAL, 'Clean old backups (keep specified number)', 10)
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Suppress output (for cron jobs)')
            ->setHelp('This command creates a full system backup including database and files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $quiet = $input->getOption('quiet');

        if (!$quiet) {
            $io->title('Clinic Management System - Backup Creation');
            $io->text('Starting backup process...');
        }

        try {
            // Create backup
            $result = $this->backupService->createFullBackup();

            if ($result['success']) {
                if (!$quiet) {
                    $io->success('Backup created successfully!');
                    $io->table(
                        ['Property', 'Value'],
                        [
                            ['Backup Name', $result['backup_name']],
                            ['Timestamp', $result['timestamp']],
                            ['Database Tables', $result['database']['tables_count'] ?? 'N/A'],
                            ['Database Size', $this->formatBytes($result['database']['size'] ?? 0)],
                            ['Files Count', $result['files']['files_count'] ?? 'N/A'],
                            ['Files Size', $this->formatBytes($result['files']['total_size'] ?? 0)],
                            ['Compressed Size', $this->formatBytes($result['compressed']['size'] ?? 0)],
                        ]
                    );
                }

                // Clean old backups if requested
                $cleanCount = $input->getOption('clean');
                if ($cleanCount !== null) {
                    if ($cleanCount === 'auto' || $cleanCount === '') {
                        // Use settings-based cleanup
                        $cleanResult = $this->backupService->cleanOldBackupsBasedOnSettings();
                    } else {
                        // Use specified count
                        $cleanResult = $this->backupService->cleanOldBackups((int)$cleanCount);
                    }
                    
                    if (!$quiet && $cleanResult['deleted_count'] > 0) {
                        $io->note(sprintf(
                            'Cleaned %d old backup(s), keeping %d most recent backups.',
                            $cleanResult['deleted_count'],
                            $cleanResult['remaining_count']
                        ));
                    }
                }

                return Command::SUCCESS;
            } else {
                if (!$quiet) {
                    $io->error('Backup creation failed!');
                    $io->text('Error: ' . ($result['error'] ?? 'Unknown error'));
                }
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            if (!$quiet) {
                $io->error('Backup creation failed with exception!');
                $io->text('Error: ' . $e->getMessage());
            }
            return Command::FAILURE;
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
} 