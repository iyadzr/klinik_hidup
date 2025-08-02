# Database Migration Instructions

This document explains how to apply the missing queue table columns that were identified during deployment.

## Automatic Migration (Recommended)

The Dockerfile has been updated to automatically run migrations when containers start. Simply rebuild and restart:

```bash
# Pull latest changes
git pull

# Rebuild and restart containers
docker compose down
docker compose up -d --build
```

The startup script will:
1. Wait for database connection
2. Run all pending migrations automatically
3. Clear application cache
4. Start PHP-FPM

## Manual Migration Options

### Option 1: Using Doctrine Migrations

```bash
# Run the specific migration
docker compose exec app php bin/console doctrine:migrations:execute Version20250802000000_AddMissingQueueColumns --up

# Or run all pending migrations
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### Option 2: Using SQL Script

```bash
# Execute the SQL script directly
docker compose exec mysql mysql -u clinic_user -pclinic_password clinic_db < database/add_missing_queue_columns.sql
```

### Option 3: Manual SQL Commands

```bash
# Run individual SQL commands
docker compose exec mysql mysql -u clinic_user -pclinic_password clinic_db -e "
ALTER TABLE queue 
ADD COLUMN is_paid TINYINT(1) DEFAULT 0 NOT NULL,
ADD COLUMN paid_at DATETIME DEFAULT NULL,
ADD COLUMN payment_method VARCHAR(20) DEFAULT NULL,
ADD COLUMN amount DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN consultation_id INT DEFAULT NULL;
"
```

## What This Migration Adds

The migration adds the following columns to the `queue` table:

- `is_paid` - TINYINT(1) - Whether the queue entry is paid
- `paid_at` - DATETIME - When payment was made
- `payment_method` - VARCHAR(20) - Payment method used
- `amount` - DECIMAL(10,2) - Payment amount
- `consultation_id` - INT - Link to consultation record
- `metadata` - LONGTEXT - Additional queue metadata
- `updated_at` - DATETIME - Auto-updated timestamp

It also:
- Updates `status` column to VARCHAR(50) (from 20)
- Adds foreign key constraint for `consultation_id`
- Adds appropriate indexes

## Verification

After migration, verify the columns were added:

```bash
docker compose exec mysql mysql -u clinic_user -pclinic_password clinic_db -e "DESCRIBE queue;"
```

You should see all the new columns listed.

## Testing

Test that the APIs work:

```bash
# Test patient registration
curl -X POST http://localhost:8090/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"superadmin","password":"password"}'

# Use the token from above to test ongoing consultations
curl http://localhost:8090/api/consultations/ongoing \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Troubleshooting

If migration fails:
1. Check database connection
2. Ensure MySQL is fully started
3. Check for existing data conflicts
4. Review container logs: `docker compose logs app`

The migration is designed to be safe and idempotent - it can be run multiple times without issues.