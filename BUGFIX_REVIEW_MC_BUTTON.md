# Bug Fix: Missing Review MC Button

## Issue
The "Review MC" button was not appearing in the Medical Certificate section, even when the "Issue Medical Certificate (MC)" checkbox was checked.

## Root Cause
**Overly Complex Visibility Condition**: The button had an extremely complex `v-if` condition that required multiple conditions to be met simultaneously:

### Original Condition:
```vue
v-if="consultation.hasMedicalCertificate && ((isGroupConsultation && groupPatients && groupPatients.length > 1) ? mcSelectedPatientIds.some(patientId => { const patient = groupPatients.find(p => p.id === patientId); return patient && patient.mcStartDate && patient.mcEndDate; }) : (consultation.mcStartDate && consultation.mcEndDate && selectedPatient))"
```

This condition required:
1. ✅ `consultation.hasMedicalCertificate` to be true (checkbox checked)
2. ❌ For single consultations: `consultation.mcStartDate && consultation.mcEndDate && selectedPatient`
3. ❌ For group consultations: Complex patient validation with MC dates

The issue was that the MC start/end dates might not be set immediately when the checkbox is checked, and `selectedPatient` might not be available in all scenarios.

## Fix Applied

### Simplified Visibility Condition:
**File**: `assets/js/views/consultations/ConsultationForm.vue`

**Before**:
```vue
<button type="button" class="btn btn-outline-info btn-sm" @click="showMCPreview" v-if="consultation.hasMedicalCertificate && ((isGroupConsultation && groupPatients && groupPatients.length > 1) ? mcSelectedPatientIds.some(patientId => { const patient = groupPatients.find(p => p.id === patientId); return patient && patient.mcStartDate && patient.mcEndDate; }) : (consultation.mcStartDate && consultation.mcEndDate && selectedPatient))">
```

**After**:
```vue
<button type="button" class="btn btn-outline-info btn-sm" @click="showMCPreview" v-if="consultation.hasMedicalCertificate">
```

### Benefits of the Fix:
✅ **Simple and Reliable**: Button appears immediately when MC checkbox is checked  
✅ **User-Friendly**: Users can preview MC even if dates aren't set yet  
✅ **No Edge Cases**: Works in all consultation scenarios (single/group)  
✅ **Better UX**: Allows users to see MC preview template before filling dates  

## Verification

### Components Verified:
- ✅ `showMCPreview()` method exists and works correctly
- ✅ MC Preview modal (`mcPreviewModal`) exists in template
- ✅ Bootstrap Modal properly imported
- ✅ Modal content displays MC template correctly

### Expected Behavior:
1. User checks "Issue Medical Certificate (MC)" checkbox
2. "Review MC" button appears immediately
3. Clicking button opens modal with MC preview
4. Modal shows MC template with current data (dates, patient info, etc.)

## Status
🟢 **RESOLVED** - Review MC button now appears when MC checkbox is checked.

## Related Files
- `assets/js/views/consultations/ConsultationForm.vue` (Fixed button visibility)

## Prevention
- ✅ Use simple, clear visibility conditions
- ✅ Avoid complex nested ternary operations in templates
- ✅ Test button visibility in different scenarios
- ✅ Prioritize user experience over complex validation 