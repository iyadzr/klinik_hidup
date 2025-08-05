#!/bin/bash

# Database initialization script for Clinic Management System
# This script ensures the database schema is properly set up with all required columns

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m' 
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
MYSQL_CONTAINER="klinik_hidup-mysql-1"
MYSQL_USER="clinic_user"
MYSQL_PASSWORD="clinic_password"
MYSQL_DATABASE="clinic_db"

# Function to check if MySQL container is running
check_mysql_container() {
    if ! docker ps | grep -q "$MYSQL_CONTAINER"; then
        echo -e "${RED}❌ MySQL container $MYSQL_CONTAINER is not running${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ MySQL container is running${NC}"
}

# Function to wait for MySQL to be ready
wait_for_mysql() {
    echo -e "${YELLOW}⏳ Waiting for MySQL to be ready...${NC}"
    
    MAX_ATTEMPTS=30
    ATTEMPT=0
    
    while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
        if docker exec "$MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1;" "$MYSQL_DATABASE" >/dev/null 2>&1; then
            echo -e "${GREEN}✅ MySQL is ready${NC}"
            return 0
        fi
        
        ATTEMPT=$((ATTEMPT + 1))
        echo -e "${BLUE}  ⏳ Attempt $ATTEMPT/$MAX_ATTEMPTS...${NC}"
        sleep 2
    done
    
    echo -e "${RED}❌ MySQL is not ready after $MAX_ATTEMPTS attempts${NC}"
    exit 1
}

# Function to run schema initialization
init_schema() {
    echo -e "${YELLOW}🔄 Initializing database schema...${NC}"
    
    if [ -f "./docker/mysql/init-schema.sql" ]; then
        echo -e "${BLUE}  📝 Running schema initialization script...${NC}"
        if docker exec -i "$MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < ./docker/mysql/init-schema.sql; then
            echo -e "${GREEN}  ✅ Schema initialization completed${NC}"
        else
            echo -e "${RED}  ❌ Schema initialization failed${NC}"
            exit 1
        fi
    else
        echo -e "${RED}  ❌ Schema initialization script not found${NC}"
        exit 1
    fi
}

# Function to run migrations
run_migrations() {
    echo -e "${YELLOW}🔄 Running database migrations...${NC}"
    
    # Check for payment columns migration
    if [ -f "./migrations/add_payment_columns_to_queue.sql" ]; then
        PAYMENT_COLUMNS_EXIST=$(docker exec "$MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "DESCRIBE queue;" 2>/dev/null | grep is_paid | wc -l)
        if [ "$PAYMENT_COLUMNS_EXIST" -eq 0 ]; then
            echo -e "${BLUE}  📝 Applying payment columns migration...${NC}"
            docker exec -i "$MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < ./migrations/add_payment_columns_to_queue.sql
            echo -e "${GREEN}  ✅ Payment columns migration completed${NC}"
        else
            echo -e "${GREEN}  ✅ Payment columns migration already applied${NC}"
        fi
    fi
    
    # Check for updated_at migration (legacy)
    if [ -f "./migrations/add_updated_at_to_queue.sql" ]; then
        UPDATED_AT_EXISTS=$(docker exec "$MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "DESCRIBE queue;" 2>/dev/null | grep updated_at | wc -l)
        if [ "$UPDATED_AT_EXISTS" -eq 0 ]; then
            echo -e "${BLUE}  📝 Applying updated_at migration...${NC}"
            docker exec -i "$MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < ./migrations/add_updated_at_to_queue.sql
            echo -e "${GREEN}  ✅ Updated_at migration completed${NC}"
        else
            echo -e "${GREEN}  ✅ Updated_at migration already applied${NC}"
        fi
    fi
}

# Function to verify schema
verify_schema() {
    echo -e "${YELLOW}🔍 Verifying database schema...${NC}"
    
    REQUIRED_COLUMNS=("is_paid" "paid_at" "payment_method" "amount" "metadata" "updated_at")
    MISSING_COLUMNS=0
    
    for col in "${REQUIRED_COLUMNS[@]}"; do
        if docker exec "$MYSQL_CONTAINER" mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "DESCRIBE queue;" 2>/dev/null | grep -q "$col"; then
            echo -e "${GREEN}  ✅ Found column: $col${NC}"
        else
            echo -e "${RED}  ❌ Missing column: $col${NC}"
            MISSING_COLUMNS=$((MISSING_COLUMNS + 1))
        fi
    done
    
    if [ "$MISSING_COLUMNS" -eq 0 ]; then
        echo -e "${GREEN}🎉 All required columns are present!${NC}"
        return 0
    else
        echo -e "${RED}❌ Found $MISSING_COLUMNS missing columns${NC}"
        return 1
    fi
}

# Main execution
main() {
    echo -e "${BLUE}🚀 Starting database initialization...${NC}"
    
    check_mysql_container
    wait_for_mysql
    
    # Try schema initialization first (preferred method)
    if init_schema; then
        echo -e "${GREEN}✅ Schema initialization successful${NC}"
    else
        echo -e "${YELLOW}⚠️  Schema initialization failed, trying migrations...${NC}"
        run_migrations
    fi
    
    # Verify the schema is correct
    if verify_schema; then
        echo -e "${GREEN}🎉 Database initialization completed successfully!${NC}"
        exit 0
    else
        echo -e "${RED}❌ Database initialization failed - schema verification failed${NC}"
        exit 1
    fi
}

# Run main function
main "$@"