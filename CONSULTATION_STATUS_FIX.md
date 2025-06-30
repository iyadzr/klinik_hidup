# Consultation Status Column Fix

## 🚨 Problem Description

The consultation saving process was failing with the following error:

```
Error creating consultation: An exception occurred while executing a query: 
SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'status' at row 1
```

## 🔍 Root Cause Analysis

The issue occurred because:

1. **Database Schema Limitation**: The `status` columns in both `consultation` and `queue` tables were defined as `VARCHAR(20)` (20 characters maximum)

2. **Application Logic**: The ConsultationController sets status values that exceed 20 characters:
   - `'completed_consultation'` = **22 characters** ❌ (too long)
   - `'in_consultation'` = **15 characters** ✅ (fits)
   - `'waiting'` = **7 characters** ✅ (fits)
   - `'completed'` = **9 characters** ✅ (fits)

3. **Entity Definition Mismatch**: The Consultation entity defines the status field as `length: 50`, but the actual database column was only 20 characters.

## 🛠 Solution Implemented

### Files Created

1. **SQL Fix File**: `fix_consultation_status_column.sql`
   ```sql
   ALTER TABLE consultation MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending';
   ALTER TABLE queue MODIFY COLUMN status VARCHAR(50) DEFAULT 'waiting';
   ```

2. **Doctrine Migration**: `migrations/Version20250130000000_FixConsultationStatusLength.php`
   - Provides proper migration for Doctrine ORM
   - Includes both up and down migration methods

3. **Automated Fix Script**: `fix_status_columns.sh`
   - Detects if Docker is running
   - Applies the fix automatically or provides manual instructions

### Database Changes Applied

| Table | Column | Before | After |
|-------|--------|--------|-------|
| `consultation` | `status` | `VARCHAR(20)` | `VARCHAR(50)` |
| `queue` | `status` | `VARCHAR(20)` | `VARCHAR(50)` |

## 📋 How to Apply the Fix

### Option 1: Automated Script (Recommended)
```bash
./fix_status_columns.sh
```

### Option 2: Manual SQL Application
```bash
mysql -u [username] -p [database_name] < fix_consultation_status_column.sql
```

### Option 3: Via Docker (if running)
```bash
docker-compose exec app mysql -u root -proot clinic_management < fix_consultation_status_column.sql
```

### Option 4: Via Doctrine Migration
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## ✅ Expected Results After Fix

1. **Consultation Saving**: Will work properly without the "Data too long" error
2. **Status Values**: All status values will be stored correctly:
   - ✅ `'completed_consultation'` (22 chars)
   - ✅ `'in_consultation'` (15 chars)
   - ✅ `'waiting'` (7 chars)
   - ✅ `'completed'` (9 chars)
   - ✅ `'pending'` (7 chars)

## 🔧 Technical Details

### ConsultationController Status Updates
The controller sets these status values in different scenarios:

```php
// Line 277: When consultation is completed
$consultation->setStatus('completed_consultation');

// Line 273: Queue status update
$queue->setStatus('completed_consultation');

// Line 172: Dynamic status from request
$consultation->setStatus($data['status']);
```

### Entity Definition
```php
#[ORM\Column(length: 50, nullable: true)]
private ?string $status = 'pending';
```

## 🧪 Testing the Fix

After applying the fix, test with this curl command:

```bash
curl 'http://127.0.0.1:8090/api/consultations' \
  -H 'Authorization: Bearer [YOUR_JWT_TOKEN]' \
  -H 'Content-Type: application/json' \
  --data-raw '{
    "patientId": 14,
    "doctorId": 1,
    "status": "completed",
    "notes": "test consultation"
  }'
```

## 🚀 Benefits

1. **Prevents Data Truncation**: No more status values being cut off
2. **Future-Proof**: Column can now handle longer status descriptions
3. **Consistency**: Database schema matches entity definitions
4. **Reliability**: Consultation saving process works reliably

## 📝 Notes

- This fix is backward compatible
- Existing data will not be affected
- The change applies to both `consultation` and `queue` tables
- Default values are preserved (`'pending'` for consultation, `'waiting'` for queue)

---

**Status**: ✅ Ready to apply
**Priority**: 🔥 Critical (blocks consultation saving)
**Impact**: 🎯 Fixes consultation form submission errors 