/**
 * NRIC Formatting Utility
 * Formats Malaysian NRIC to YYMMDD-XX-XXXX format for better readability
 */

/**
 * Format NRIC input to YYMMDD-XX-XXXX format
 * @param {string} input - Raw NRIC input
 * @returns {string} - Formatted NRIC with dashes
 */
export function formatNRIC(input) {
  if (!input) return '';
  
  // Remove all non-digit characters
  const cleanInput = input.replace(/\D/g, '');
  
  // Only format if we have 12 digits
  if (cleanInput.length === 12) {
    return `${cleanInput.substring(0, 6)}-${cleanInput.substring(6, 8)}-${cleanInput.substring(8, 12)}`;
  }
  
  // For partial input, add dashes as user types
  if (cleanInput.length >= 6 && cleanInput.length < 8) {
    return `${cleanInput.substring(0, 6)}-${cleanInput.substring(6)}`;
  } else if (cleanInput.length >= 8) {
    return `${cleanInput.substring(0, 6)}-${cleanInput.substring(6, 8)}-${cleanInput.substring(8)}`;
  }
  
  return cleanInput;
}

/**
 * Remove dashes from NRIC for validation/storage purposes
 * @param {string} nric - NRIC with or without dashes
 * @returns {string} - Clean NRIC without dashes
 */
export function cleanNRIC(nric) {
  if (!nric) return '';
  return nric.replace(/\D/g, '');
}

/**
 * Validate NRIC format (12 digits)
 * @param {string} nric - NRIC to validate
 * @returns {boolean} - True if valid 12-digit NRIC
 */
export function isValidNRIC(nric) {
  const clean = cleanNRIC(nric);
  return clean.length === 12 && /^\d{12}$/.test(clean);
}

/**
 * Handle NRIC input formatting in real-time
 * This function should be called on input events
 * @param {Event} event - Input event
 * @returns {string} - Formatted NRIC value
 */
export function handleNRICInput(event) {
  const input = event.target;
  const cursorPosition = input.selectionStart;
  const oldValue = input.value;
  const newValue = formatNRIC(oldValue);
  
  // Update the input value
  input.value = newValue;
  
  // Adjust cursor position to account for added dashes
  let newCursorPosition = cursorPosition;
  if (newValue.length > oldValue.length) {
    // Dashes were added, adjust cursor position
    if (cursorPosition === 6 || cursorPosition === 9) {
      newCursorPosition = cursorPosition + 1;
    }
  }
  
  // Set cursor position
  setTimeout(() => {
    input.setSelectionRange(newCursorPosition, newCursorPosition);
  }, 0);
  
  return newValue;
}

/**
 * Vue directive for automatic NRIC formatting
 */
export const nricDirective = {
  mounted(el, binding) {
    el.addEventListener('input', (e) => {
      const formatted = handleNRICInput(e);
      // Trigger Vue reactivity
      if (binding.value && typeof binding.value === 'function') {
        binding.value(formatted);
      }
    });
    
    el.addEventListener('blur', (e) => {
      // Final formatting on blur
      e.target.value = formatNRIC(e.target.value);
      if (binding.value && typeof binding.value === 'function') {
        binding.value(e.target.value);
      }
    });
  }
}; 