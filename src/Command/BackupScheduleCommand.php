<?php

namespace App\Command;

use App\Service\BackupService;
use App\Repository\SettingRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backup:schedule',
    description: 'Generate cron schedule for automated backups based on admin settings',
)]
class BackupScheduleCommand extends Command
{
    private BackupService $backupService;
    private SettingRepository $settingRepository;
    private string $projectDir;

    public function __construct(BackupService $backupService, SettingRepository $settingRepository, string $projectDir)
    {
        $this->backupService = $backupService;
        $this->settingRepository = $settingRepository;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('show-only', 's', InputOption::VALUE_NONE, 'Only show the cron schedule without updating')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force update cron schedule even if backups are disabled')
            ->setHelp('This command generates and updates the cron schedule for automated backups based on admin settings.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $showOnly = $input->getOption('show-only');
        $force = $input->getOption('force');

        $io->title('Backup Schedule Management');

        // Check if backups are enabled
        $backupEnabled = $this->backupService->isBackupEnabled();
        $backupTime = $this->backupService->getBackupTime();
        $backupFrequency = $this->backupService->getBackupFrequency();

        $io->table(
            ['Setting', 'Value'],
            [
                ['Backup Enabled', $backupEnabled ? 'Yes' : 'No'],
                ['Backup Time', $backupTime],
                ['Backup Frequency', ucfirst($backupFrequency)],
            ]
        );

        if (!$backupEnabled && !$force) {
            $io->warning('Automatic backups are disabled in admin settings.');
            $io->note('Use --force to generate schedule anyway, or enable backups in admin settings.');
            return Command::SUCCESS;
        }

        // Generate cron schedule
        $cronSchedule = $this->generateCronSchedule($backupTime, $backupFrequency);
        $cronCommand = $this->generateCronCommand();

        $io->section('Generated Cron Configuration');
        $io->text("Schedule: <info>$cronSchedule</info>");
        $io->text("Command: <info>$cronCommand</info>");
        $io->newLine();

        if ($showOnly) {
            $io->success('Cron schedule generated (display only)');
            return Command::SUCCESS;
        }

        // Ask for confirmation
        if (!$io->confirm('Do you want to update the cron schedule?', false)) {
            $io->note('Cron schedule update cancelled');
            return Command::SUCCESS;
        }

        // Update cron schedule
        try {
            $this->updateCronSchedule($cronSchedule, $cronCommand);
            $io->success('Cron schedule updated successfully!');
            
            $io->section('Current Cron Jobs');
            $this->showCurrentCronJobs($io);
            
        } catch (\Exception $e) {
            $io->error('Failed to update cron schedule: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function generateCronSchedule(string $time, string $frequency): string
    {
        [$hour, $minute] = explode(':', $time);

        switch ($frequency) {
            case 'daily':
                return "$minute $hour * * *";
            case 'weekly':
                return "$minute $hour * * 0"; // Sunday
            case 'monthly':
                return "$minute $hour 1 * *"; // First day of month
            default:
                return "$minute $hour * * *"; // Default to daily
        }
    }

    private function generateCronCommand(): string
    {
        $phpBin = $this->findPhpBinary();
        $consolePath = $this->projectDir . '/bin/console';
        
        return "cd {$this->projectDir} && $phpBin $consolePath app:backup:create --quiet --clean";
    }

    private function findPhpBinary(): string
    {
        // Try to find PHP binary
        $phpPaths = [
            '/usr/bin/php',
            '/usr/local/bin/php',
            '/opt/homebrew/bin/php', // macOS Homebrew
            'php' // Fallback to PATH
        ];

        foreach ($phpPaths as $path) {
            if ($path === 'php' || file_exists($path)) {
                return $path;
            }
        }

        return 'php'; // Fallback
    }

    private function updateCronSchedule(string $schedule, string $command): void
    {
        // Get current crontab
        $currentCron = shell_exec('crontab -l 2>/dev/null') ?: '';
        
        // Remove existing backup cron jobs
        $cronLines = array_filter(
            explode("\n", $currentCron),
            fn($line) => !str_contains($line, 'app:backup:create')
        );

        // Add new backup cron job
        $cronLines[] = "# Clinic Management System - Automated Backup";
        $cronLines[] = "$schedule $command";

        // Update crontab
        $newCron = implode("\n", array_filter($cronLines));
        $tempFile = tempnam(sys_get_temp_dir(), 'cron');
        file_put_contents($tempFile, $newCron . "\n");
        
        $result = shell_exec("crontab $tempFile 2>&1");
        unlink($tempFile);
        
        if ($result !== null && trim($result) !== '') {
            throw new \Exception("Crontab update failed: $result");
        }
    }

    private function showCurrentCronJobs(SymfonyStyle $io): void
    {
        $cronJobs = shell_exec('crontab -l 2>/dev/null') ?: '';
        
        if (empty(trim($cronJobs))) {
            $io->text('No cron jobs found');
            return;
        }

        $lines = explode("\n", trim($cronJobs));
        foreach ($lines as $line) {
            if (!empty(trim($line))) {
                $io->text($line);
            }
        }
    }
} 