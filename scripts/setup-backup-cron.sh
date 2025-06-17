#!/bin/bash

# Clinic Management System - Backup Cron Setup Script
# This script helps set up automated backups using cron jobs

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get the project directory (assuming script is in project_root/scripts/)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN=$(which php)
CONSOLE_PATH="$PROJECT_DIR/bin/console"

echo -e "${BLUE}=== Clinic Management System - Backup Cron Setup ===${NC}"
echo -e "Project Directory: ${GREEN}$PROJECT_DIR${NC}"
echo -e "PHP Binary: ${GREEN}$PHP_BIN${NC}"
echo -e "Console Path: ${GREEN}$CONSOLE_PATH${NC}"
echo ""

# Check if console exists
if [ ! -f "$CONSOLE_PATH" ]; then
    echo -e "${RED}Error: Symfony console not found at $CONSOLE_PATH${NC}"
    exit 1
fi

# Check if PHP is available
if [ -z "$PHP_BIN" ]; then
    echo -e "${RED}Error: PHP binary not found in PATH${NC}"
    exit 1
fi

# Test the backup command
echo -e "${YELLOW}Testing backup command...${NC}"
cd "$PROJECT_DIR"
$PHP_BIN $CONSOLE_PATH app:backup:create --quiet --dry-run 2>/dev/null
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Backup command is working${NC}"
else
    echo -e "${YELLOW}⚠ Backup command test failed, but this might be normal${NC}"
fi

echo ""
echo -e "${BLUE}=== Cron Job Options ===${NC}"
echo "1. Daily backup at 2:00 AM (recommended)"
echo "2. Daily backup at 3:00 AM"
echo "3. Twice daily (6:00 AM and 6:00 PM)"
echo "4. Weekly backup (Sunday at 2:00 AM)"
echo "5. Custom schedule"
echo "6. Show current cron jobs"
echo "7. Remove backup cron jobs"
echo ""

read -p "Choose an option (1-7): " choice

case $choice in
    1)
        CRON_SCHEDULE="0 2 * * *"
        DESCRIPTION="Daily at 2:00 AM"
        ;;
    2)
        CRON_SCHEDULE="0 3 * * *"
        DESCRIPTION="Daily at 3:00 AM"
        ;;
    3)
        CRON_SCHEDULE="0 6,18 * * *"
        DESCRIPTION="Twice daily at 6:00 AM and 6:00 PM"
        ;;
    4)
        CRON_SCHEDULE="0 2 * * 0"
        DESCRIPTION="Weekly on Sunday at 2:00 AM"
        ;;
    5)
        echo ""
        echo "Cron schedule format: minute hour day month weekday"
        echo "Examples:"
        echo "  0 2 * * *     = Daily at 2:00 AM"
        echo "  30 14 * * 1   = Every Monday at 2:30 PM"
        echo "  0 */6 * * *   = Every 6 hours"
        echo ""
        read -p "Enter custom cron schedule: " CRON_SCHEDULE
        DESCRIPTION="Custom schedule: $CRON_SCHEDULE"
        ;;
    6)
        echo -e "${BLUE}Current cron jobs containing 'backup':${NC}"
        crontab -l 2>/dev/null | grep -i backup || echo "No backup cron jobs found"
        exit 0
        ;;
    7)
        echo -e "${YELLOW}Removing backup cron jobs...${NC}"
        # Create a temporary file with cron jobs excluding backup ones
        crontab -l 2>/dev/null | grep -v "app:backup:create" > /tmp/cron_temp
        crontab /tmp/cron_temp
        rm /tmp/cron_temp
        echo -e "${GREEN}✓ Backup cron jobs removed${NC}"
        exit 0
        ;;
    *)
        echo -e "${RED}Invalid option${NC}"
        exit 1
        ;;
esac

# Create the cron command
CRON_COMMAND="cd $PROJECT_DIR && $PHP_BIN $CONSOLE_PATH app:backup:create --quiet --clean=auto"

echo ""
echo -e "${BLUE}=== Cron Job Configuration ===${NC}"
echo -e "Schedule: ${GREEN}$DESCRIPTION${NC}"
echo -e "Command: ${GREEN}$CRON_COMMAND${NC}"
echo ""

read -p "Do you want to add this cron job? (y/N): " confirm

if [[ $confirm =~ ^[Yy]$ ]]; then
    # Add the cron job
    (crontab -l 2>/dev/null; echo "# Clinic Management System - Automated Backup") | crontab -
    (crontab -l 2>/dev/null; echo "$CRON_SCHEDULE $CRON_COMMAND") | crontab -
    
    echo -e "${GREEN}✓ Cron job added successfully!${NC}"
    echo ""
    echo -e "${BLUE}Current cron jobs:${NC}"
    crontab -l
    
    echo ""
    echo -e "${YELLOW}Important Notes:${NC}"
    echo "• Backups will be stored in: $PROJECT_DIR/var/backups/"
    echo "• Old backups will be automatically cleaned (keeping 10 most recent)"
    echo "• Make sure the web server user has write permissions to the backup directory"
    echo "• Monitor the backup logs for any issues"
    echo "• You can manually create a backup with: php bin/console app:backup:create"
    
    # Create backup directory if it doesn't exist
    mkdir -p "$PROJECT_DIR/var/backups"
    chmod 755 "$PROJECT_DIR/var/backups"
    
    echo ""
    echo -e "${GREEN}Setup complete!${NC}"
else
    echo -e "${YELLOW}Cron job setup cancelled${NC}"
fi 