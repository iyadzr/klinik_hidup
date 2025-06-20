# Clinic Management System - Production Setup Guide

## Overview

This guide outlines the robust production configuration implemented to handle high load, prevent system hangs, and ensure reliable operation under heavy traffic conditions.

## Performance Optimizations Implemented

### 1. Database Layer Optimizations

#### Connection Pooling & Management
- **Max Connections**: 50 concurrent connections with proper pooling
- **Connection Timeout**: 10 seconds to prevent hanging connections
- **Idle Timeout**: 60 seconds for automatic cleanup
- **MySQL Performance Tuning**:
  - InnoDB buffer pool: 256MB
  - Query cache: 32MB  
  - Slow query logging enabled (>2 seconds)
  - Connection limit: 200

#### Caching Strategy
- **Redis Integration**: All caching moved to Redis for consistency
- **Query Caching**: Doctrine query and result caching with Redis
- **Metadata Caching**: Entity metadata cached for 24 hours
- **Session Storage**: Redis-based session handling

### 2. Backend Performance

#### Enhanced Repository Pattern
- **BaseRepository**: Custom base with caching, pagination, and error handling
- **Array Hydration**: Used for search queries to avoid object instantiation overhead
- **Batch Operations**: Optimized bulk inserts with memory management
- **Health Checks**: Built-in repository health monitoring

#### API Optimizations
- **Rate Limiting**: 
  - General API: 1000 requests/hour
  - Authentication: 20 requests/minute
  - Heavy operations: 100 requests/hour
- **Request Timeouts**: 30-second maximum with abort controllers
- **Error Handling**: Comprehensive logging with fallback mechanisms
- **Response Caching**: Client-side headers for appropriate endpoints

### 3. Frontend Robustness

#### Request Management
- **Abort Controllers**: Cancel pending requests when new ones are made
- **Timeout Protection**: 30-second request timeouts to prevent hanging
- **Cache Fallbacks**: SessionStorage fallbacks for critical data
- **Debounced Operations**: Prevent spam requests from rapid user interactions

#### Real-time Updates (SSE)
- **Connection Management**: Maximum 1-hour connections to prevent memory leaks
- **Automatic Reconnection**: 5-second retry on connection failures
- **Heartbeat Monitoring**: 30-second keepalive messages
- **Resource Cleanup**: Proper connection termination and logging

### 4. Infrastructure Optimizations

#### Docker Configuration
- **Resource Limits**: CPU and memory limits for each service
- **Health Checks**: Container health monitoring
- **Restart Policies**: Automatic restart on failures
- **Volume Optimization**: Cached mounts for better performance

#### Nginx Configuration
- **Worker Optimization**: Auto-scaling workers with 4096 connections each
- **Compression**: Gzip compression for all text-based content
- **Static Caching**: 1-year cache for static assets
- **Rate Limiting**: Multiple rate limit zones for different endpoints
- **Security Headers**: Comprehensive security header implementation

#### PHP-FPM Tuning
- **OPcache**: Aggressive caching with 256MB memory allocation
- **Memory Limits**: 512MB per process
- **Session Handling**: Redis-based session storage
- **Error Handling**: Production-safe error reporting

### 5. Monitoring & Health Checks

#### Performance Monitoring
- **Health Check Endpoint**: `/api/performance/health`
- **Database Metrics**: Connection and query performance monitoring
- **Memory Monitoring**: Real-time memory usage tracking
- **Disk Space Monitoring**: Storage capacity alerts

#### Logging Strategy
- **Structured Logging**: JSON-formatted logs with context
- **Error Tracking**: Comprehensive error logging with stack traces
- **Performance Logging**: Request timing and resource usage
- **SSE Connection Tracking**: Real-time connection monitoring

## Production Deployment Steps

### 1. Environment Setup

```bash
# Copy production environment template
cp .env.prod .env

# Update with your production values:
# - Strong passwords for all services
# - Correct domain names
# - SSL certificate paths
# - SMTP configuration
```

### 2. SSL Certificate Setup

```bash
# Create SSL directory
mkdir -p docker/ssl

# Add your SSL certificates
# docker/ssl/cert.pem
# docker/ssl/private.key
```

### 3. Deploy with Docker Compose

```bash
# Deploy production stack
docker-compose -f docker-compose.prod.yml up -d

# Verify all services are running
docker-compose -f docker-compose.prod.yml ps

# Check logs
docker-compose -f docker-compose.prod.yml logs -f
```

### 4. Database Initialization

```bash
# Run database migrations
docker-compose -f docker-compose.prod.yml exec php php bin/console doctrine:migrations:migrate --no-interaction

# Load medication fixtures
docker-compose -f docker-compose.prod.yml exec php php bin/console doctrine:fixtures:load --group=medications --no-interaction

# Create initial admin user
docker-compose -f docker-compose.prod.yml exec php php bin/console app:create-initial-users
```

### 5. Performance Verification

#### Check System Health
```bash
curl https://your-domain.com/api/performance/health
```

Expected response:
```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00+00:00",
  "response_time": "25.45ms",
  "checks": {
    "database": {"status": "healthy", "response_time": "12.3ms"},
    "memory": {"status": "healthy", "usage_percent": 35.2},
    "disk": {"status": "healthy", "usage_percent": 45.8}
  }
}
```

#### Load Testing
```bash
# Install Apache Bench or similar tool
# Test API endpoints
ab -n 1000 -c 10 https://your-domain.com/api/patients/search?query=test

# Test SSE endpoints
curl -N https://your-domain.com/api/sse/queue-updates
```

## Monitoring Setup

### 1. Enable Prometheus + Grafana (Optional)
```bash
# Deploy with monitoring stack
docker-compose -f docker-compose.prod.yml --profile monitoring up -d

# Access Grafana at http://your-domain:3000
# Default: admin / [GRAFANA_PASSWORD from .env]
```

### 2. Log Monitoring
```bash
# Monitor application logs
docker-compose -f docker-compose.prod.yml logs -f php

# Monitor nginx access logs
docker-compose -f docker-compose.prod.yml logs -f nginx

# Monitor MySQL slow queries
docker-compose -f docker-compose.prod.yml exec mysql tail -f /var/log/mysql/slow.log
```

## Troubleshooting Common Issues

### High Memory Usage
1. Check container memory limits
2. Monitor PHP memory usage via health endpoint
3. Adjust OPcache settings if needed
4. Check for memory leaks in long-running processes

### Slow Response Times
1. Check database query performance
2. Monitor Redis cache hit rates
3. Review nginx access logs for slow requests
4. Check network latency between services

### Database Connection Issues
1. Verify connection pool settings
2. Check MySQL max_connections setting
3. Monitor active connection count
4. Review database error logs

### SSE Connection Problems
1. Check proxy configuration for SSE endpoints
2. Monitor SSE connection logs
3. Verify heartbeat messages are being sent
4. Check client reconnection logic

## Security Considerations

### 1. Update Default Passwords
- Change all default passwords in `.env`
- Use strong, unique passwords for each service
- Enable two-factor authentication where possible

### 2. SSL/TLS Configuration
- Use valid SSL certificates (Let's Encrypt recommended)
- Enable HSTS headers
- Configure secure cipher suites

### 3. Rate Limiting
- Monitor rate limit violations in logs
- Adjust limits based on actual usage patterns
- Implement IP-based blocking for abusive requests

### 4. Security Headers
- All major security headers are configured
- Content Security Policy prevents XSS attacks
- CORS is properly configured for API access

## Maintenance Tasks

### Daily
- Monitor system health endpoint
- Check application and error logs
- Verify backup completion

### Weekly  
- Review performance metrics
- Update security patches
- Clean up old log files

### Monthly
- Database optimization and cleanup
- Review and update SSL certificates
- Performance audit and optimization

## Support & Monitoring URLs

- **Health Check**: `https://your-domain.com/api/performance/health`
- **SSE Health**: `https://your-domain.com/api/sse/health`
- **Grafana Dashboard**: `http://your-domain:3000` (if enabled)
- **Prometheus Metrics**: `http://your-domain:9090` (if enabled)

This production setup ensures your clinic management system can handle high concurrent loads, prevent system hangs, and maintain reliable operation even under stress conditions. 