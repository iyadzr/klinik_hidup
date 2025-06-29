# Bug Fix: Visit Histories 404 Error

## Issue
The consultation form was throwing a 404 error when trying to load patient visit histories:
```
requestManager.js:107 ❌ Request failed: load-visit-histories-11 (ID: tpdujao9m) AxiosError {message: 'Request failed with status code 404', ...}
```

## Root Cause
**API Endpoint Mismatch**: The frontend was calling a non-existent API endpoint.

### What Was Wrong:
```javascript
// INCORRECT - This endpoint doesn't exist
await axios.get(`/api/patients/${patientId}/visits`, { signal });
```

### What Should Be:
```javascript
// CORRECT - This endpoint exists in ConsultationController
await axios.get(`/api/consultations/patient/${patientId}`, { signal });
```

## Backend Route
The correct route exists in `src/Controller/ConsultationController.php`:
```php
#[Route('/patient/{id}', name: 'app_consultation_history', methods: ['GET'])]
public function getPatientHistory(int $id, Request $request): JsonResponse
```

Full URL: `/api/consultations/patient/{id}`

## Fix Applied

### 1. Corrected API Endpoint
**File**: `assets/js/views/consultations/ConsultationForm.vue`

**Before**:
```javascript
return await axios.get(`/api/patients/${patientId}/visits`, { signal });
```

**After**:
```javascript
return await axios.get(`/api/consultations/patient/${patientId}`, { signal });
```

### 2. Removed Duplicate Method
Found and removed a duplicate `loadVisitHistories()` method to avoid confusion.

### 3. Enhanced Data Mapping
Ensured the response data is properly mapped to match the expected format:
```javascript
this.visitHistories = response.data.map(visit => ({
  id: visit.id,
  consultationDate: visit.consultationDate,
  doctor: visit.doctor,
  diagnosis: visit.diagnosis || '',
  notes: visit.notes || ''
}));
```

## Prevention
- ✅ Always verify API endpoints exist in backend before calling from frontend
- ✅ Use consistent naming patterns for API routes
- ✅ Document API endpoints clearly
- ✅ Test API calls during development

## Status
🟢 **RESOLVED** - Visit histories now load correctly without 404 errors.

## Related Files
- `assets/js/views/consultations/ConsultationForm.vue` (Fixed)
- `src/Controller/ConsultationController.php` (Route exists)
- `assets/js/utils/requestManager.js` (Error reporting) 