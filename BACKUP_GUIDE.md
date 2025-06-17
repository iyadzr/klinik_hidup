# Clinic Management System - Backup Guide

## Overview

The Clinic Management System includes a comprehensive backup solution that automatically backs up your database and important files. This guide explains how to configure and use the backup system.

## Features

### ✅ **Automated Backups**
- Configurable backup timing (default: 6:30 PM daily)
- Multiple frequency options (daily, weekly, monthly)
- Automatic cleanup of old backups
- Cron job integration

### ✅ **Manual Backups**
- One-click backup creation from admin interface
- Immediate backup generation via command line
- Progress tracking and status updates

### ✅ **Backup Management**
- Professional admin interface
- Download backup files
- Restore from backups
- Backup history and statistics

### ✅ **What Gets Backed Up**
- Complete database (all tables, data, structure)
- Application source code (`src/`, `assets/`, `templates/`)
- Configuration files (`.env`, `config/`)
- User uploads (`public/uploads/`)
- Compressed into ZIP files for efficient storage

## Configuration

### Admin Settings

1. **Navigate to System Settings**
   - Go to Admin → System Settings
   - Click on the "System" tab

2. **Configure Backup Settings**
   - **Backup Enabled**: Enable/disable automatic backups
   - **Backup Time**: Set daily backup time (24-hour format, e.g., 18:30 for 6:30 PM)
   - **Backup Frequency**: Choose daily, weekly, or monthly
   - **Backup Retention Count**: Number of backup files to keep (default: 10)
   - **Backup Retention Days**: Days to keep backups (default: 30)

3. **Save Settings**
   - Click "Save All Changes" to apply your configuration

### Update Backup Schedule

After changing backup settings:

1. **Go to Backup Management**
   - Navigate to Admin → Backup Management

2. **Update Schedule**
   - Click "Update Schedule" button
   - This will update the cron job based on your settings

## Usage

### Manual Backup Creation

#### Via Admin Interface
1. Go to Admin → Backup Management
2. Click "Create Manual Backup"
3. Wait for completion (progress shown)
4. Download or manage the backup from the list

#### Via Command Line
```bash
# Create a backup immediately
php bin/console app:backup:create

# Create backup with custom cleanup (keep 5 most recent)
php bin/console app:backup:create --clean=5

# Create backup using settings-based cleanup
php bin/console app:backup:create --clean=auto

# Silent backup (for cron jobs)
php bin/console app:backup:create --quiet --clean=auto
```

### Automated Backup Setup

#### Method 1: Using Admin Interface (Recommended)
1. Configure backup settings in System Settings
2. Go to Backup Management
3. Click "Update Schedule" to apply cron job

#### Method 2: Using Setup Script
```bash
# Run the interactive setup script
./scripts/setup-backup-cron.sh

# Make script executable if needed
chmod +x scripts/setup-backup-cron.sh
```

#### Method 3: Manual Cron Setup
```bash
# Edit crontab
crontab -e

# Add backup job (example for daily at 6:30 PM)
30 18 * * * cd /path/to/clinic && php bin/console app:backup:create --quiet --clean=auto
```

### Backup Management

#### View Backup Status
- **Total Backups**: Number of backup files
- **Total Size**: Storage space used
- **Latest Backup**: Age of most recent backup
- **Free Space**: Available disk space

#### Download Backups
1. Go to Backup Management
2. Find the backup in the history table
3. Click the download button (📥)
4. File will be downloaded to your computer

#### Restore from Backup
1. Go to Backup Management
2. Find the backup to restore
3. Click the restore button (↶)
4. Confirm the restoration (⚠️ **This will replace current data**)
5. Wait for completion

#### Cleanup Old Backups
1. Go to Backup Management
2. Click "Cleanup" button
3. Choose how many recent backups to keep
4. Confirm cleanup operation

## Command Reference

### Backup Commands
```bash
# Create backup
php bin/console app:backup:create [options]

# Update backup schedule based on settings
php bin/console app:backup:schedule [options]
```

### Backup Create Options
- `--clean=N`: Keep N most recent backups (number or 'auto')
- `--quiet`: Suppress output (for cron jobs)

### Backup Schedule Options
- `--show-only`: Display schedule without updating
- `--force`: Update schedule even if backups disabled

## File Locations

### Backup Storage
- **Location**: `/var/backups/`
- **Format**: `full_backup_YYYY-MM-DD_HH-MM-SS.zip`
- **Contents**: Database SQL + Files + Manifest

### Backup Structure
```
full_backup_2024-01-15_18-30-00.zip
├── database.sql          # Complete database dump
├── files/                # Application files
│   ├── uploads/          # User uploads
│   ├── config/           # Configuration files
│   ├── src/              # Source code
│   ├── assets/           # Frontend assets
│   ├── templates/        # Twig templates
│   └── .env              # Environment file
└── manifest.json         # Backup metadata
```

## Troubleshooting

### Common Issues

#### "Backup creation failed"
- Check disk space availability
- Verify database connection
- Ensure write permissions on `/var/backups/`
- Check PHP memory limit and execution time

#### "mysqldump command not found"
- Install MySQL client tools
- Verify mysqldump is in system PATH
- Check database credentials in `.env`

#### "Cron job not running"
- Verify cron service is running: `sudo service cron status`
- Check cron logs: `grep CRON /var/log/syslog`
- Ensure correct file paths in cron command
- Verify PHP binary path

#### "Permission denied"
- Set proper permissions: `chmod 755 /var/backups`
- Ensure web server user can write to backup directory
- Check script execution permissions

### Manual Verification

#### Test Backup Creation
```bash
# Test backup command
php bin/console app:backup:create --quiet

# Check if backup was created
ls -la var/backups/

# Verify backup contents
unzip -l var/backups/full_backup_*.zip
```

#### Test Cron Schedule
```bash
# Show current cron jobs
crontab -l

# Test cron command manually
cd /path/to/clinic && php bin/console app:backup:create --quiet --clean=auto
```

## Best Practices

### Security
- Store backups in secure location
- Consider encrypting sensitive backups
- Limit access to backup files
- Regular backup integrity checks

### Storage Management
- Monitor disk space usage
- Set appropriate retention policies
- Consider offsite backup storage
- Regular cleanup of old backups

### Monitoring
- Check backup logs regularly
- Verify backup completion
- Test restore procedures periodically
- Monitor backup file sizes

### Scheduling
- Schedule backups during low-usage hours
- Avoid peak business times
- Consider database activity levels
- Plan for maintenance windows

## Support

For additional help:
1. Check system logs in `/var/log/`
2. Review backup command output
3. Verify system requirements
4. Contact system administrator

---

**⚠️ Important Notes:**
- Always test restore procedures before relying on backups
- Backups include sensitive data - handle securely
- Regular monitoring ensures backup reliability
- Keep multiple backup copies for redundancy 