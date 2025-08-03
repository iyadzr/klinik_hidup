# Docker Setup for Clinic Management System

This document explains the enhanced Docker setup that automatically handles database migrations and ensures all required database schema changes are applied during container startup.

## Overview

The Docker setup has been enhanced to automatically:
- Run database migrations during container startup
- Handle migration conflicts gracefully
- Ensure critical database columns exist
- Validate database schema
- Provide health checks for monitoring

## Files Structure

```
docker/
├── php/
│   ├── Dockerfile              # Main PHP container build
│   ├── startup.sh              # Development startup script
│   ├── startup-prod.sh         # Production startup script
│   ├── php.ini                 # PHP configuration
│   └── www.conf               # PHP-FPM configuration
├── nginx/
│   └── Dockerfile              # Nginx container build
└── frontend/
    └── Dockerfile              # Frontend container build

docker-compose.yml              # Development configuration
docker-compose.prod.yml         # Production configuration
```

## Startup Scripts

### Development Startup (`startup.sh`)

The development startup script includes:
- Database connection checking with timeout
- Migration status checking
- Automatic migration execution
- Migration conflict resolution
- Critical schema validation
- Cache clearing and warming
- Permission setting

### Production Startup (`startup-prod.sh`)

The production startup script includes all development features plus:
- Production-specific optimizations
- Enhanced security checks
- Cache pruning
- Longer timeouts for database operations
- Production environment validation

## Key Features

### 1. Automatic Migration Handling

The startup scripts automatically:
- Check migration status before running
- Execute pending migrations
- Resolve migration conflicts by marking all as executed if needed
- Ensure critical database columns exist (like `consultation_id`)

### 2. Database Schema Validation

The scripts check for critical columns:
- `consultation_id` in queue table
- `is_paid`, `paid_at`, `payment_method`, `amount` in queue table
- `metadata`, `updated_at` in queue table
- `dosage`, `frequency`, `duration` in prescribed_medication table
- Queue status column length (ensures VARCHAR(50) for status values like 'completed_consultation')

### 3. Health Checks

Docker health checks are configured to:
- Verify database connectivity
- Check Symfony console availability
- Monitor application health

### 4. Error Handling

All operations include proper error handling:
- Graceful fallbacks when operations fail
- Detailed logging for troubleshooting
- Non-blocking error recovery

## Usage

### Development

```bash
# Start development environment
docker-compose up -d

# View logs
docker-compose logs -f app

# Check migration status
docker-compose exec app php bin/console doctrine:migrations:status
```

### Production

```bash
# Start production environment
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Or use the production file directly
docker-compose -f docker-compose.prod.yml up -d

# View production logs
docker-compose -f docker-compose.prod.yml logs -f app
```

## Environment Variables

### Development
- `APP_ENV=dev`
- `DATABASE_URL=mysql://clinic_user:clinic_password@mysql:3306/clinic_db?serverVersion=8.0`

### Production
- `APP_ENV=prod`
- `MYSQL_ROOT_PASSWORD` (optional, defaults to `root_password`)
- `MYSQL_PASSWORD` (optional, defaults to `clinic_password`)

## Troubleshooting

### Migration Issues

If migrations fail during startup:

1. Check the logs:
   ```bash
   docker-compose logs app
   ```

2. Manually run migrations:
   ```bash
   docker-compose exec app php bin/console doctrine:migrations:migrate
   ```

3. Resolve conflicts:
   ```bash
   docker-compose exec app php bin/console doctrine:migrations:version --add --all
   ```

### Database Connection Issues

1. Check if MySQL is running:
   ```bash
   docker-compose ps mysql
   ```

2. Check MySQL logs:
   ```bash
   docker-compose logs mysql
   ```

3. Test connection manually:
   ```bash
   docker-compose exec app php -r "
   try {
       \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=clinic_db', 'clinic_user', 'clinic_password');
       \$pdo->query('SELECT 1');
       echo 'Database connection successful\n';
   } catch (Exception \$e) {
       echo 'Database connection failed: ' . \$e->getMessage() . '\n';
   }
   "
   ```

### Missing Database Columns

If you encounter "Column not found" errors:

1. The startup script should automatically add missing columns
2. Check if the column exists:
   ```bash
   docker-compose exec mysql mysql -u clinic_user -pclinic_password clinic_db -e "DESCRIBE queue;"
   ```

3. Manually add missing columns if needed:
   ```bash
   # For queue table
   docker-compose exec mysql mysql -u clinic_user -pclinic_password clinic_db -e "ALTER TABLE queue ADD COLUMN consultation_id INT DEFAULT NULL;"
   
   # For prescribed_medication table
   docker-compose exec mysql mysql -u clinic_user -pclinic_password clinic_db -e "ALTER TABLE prescribed_medication ADD COLUMN dosage VARCHAR(255) DEFAULT NULL;"
   docker-compose exec mysql mysql -u clinic_user -pclinic_password clinic_db -e "ALTER TABLE prescribed_medication ADD COLUMN frequency VARCHAR(255) DEFAULT NULL;"
   docker-compose exec mysql mysql -u clinic_user -pclinic_password clinic_db -e "ALTER TABLE prescribed_medication ADD COLUMN duration VARCHAR(255) DEFAULT NULL;"
   ```

## Monitoring

### Health Check Status

Check container health:
```bash
docker-compose ps
```

### Application Logs

View application logs:
```bash
docker-compose logs -f app
```

### Database Logs

View database logs:
```bash
docker-compose logs -f mysql
```

## Performance Optimization

### Development
- Uses development PHP settings
- Includes debug information
- Faster startup times

### Production
- Optimized PHP settings
- Enhanced MySQL configuration
- Cache optimizations
- Security hardening

## Security Considerations

### Development
- Debug mode enabled
- Detailed error reporting
- Development-friendly settings

### Production
- Debug mode disabled
- Minimal error reporting
- Security checks enabled
- Optimized for performance

## Backup and Recovery

### Database Backup

```bash
# Create backup
docker-compose exec mysql mysqldump -u clinic_user -pclinic_password clinic_db > backup.sql

# Restore backup
docker-compose exec -T mysql mysql -u clinic_user -pclinic_password clinic_db < backup.sql
```

### Volume Backup

```bash
# Backup volumes
docker run --rm -v clinic-management-system_mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql_backup.tar.gz -C /data .

# Restore volumes
docker run --rm -v clinic-management-system_mysql_data:/data -v $(pwd):/backup alpine tar xzf /backup/mysql_backup.tar.gz -C /data
```

## Conclusion

This enhanced Docker setup ensures that your clinic management system will start reliably with all necessary database schema changes applied automatically. The startup scripts handle common issues like migration conflicts and missing columns, making the deployment process more robust and user-friendly. 