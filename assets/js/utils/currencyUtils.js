/**
 * Currency Formatting Utility
 * Ensures all currency values are displayed with exactly 2 decimal places
 */

/**
 * Format currency value to always show 2 decimal places
 * @param {number|string} amount - The amount to format
 * @param {string} currency - Currency symbol (default: 'RM')
 * @param {boolean} includeSymbol - Whether to include currency symbol (default: false)
 * @returns {string} - Formatted currency string
 */
export function formatCurrency(amount, currency = 'RM', includeSymbol = false) {
  const numericAmount = parseFloat(amount || 0);
  const formatted = numericAmount.toFixed(2);
  
  if (includeSymbol) {
    return `${currency} ${formatted}`;
  }
  
  return formatted;
}

/**
 * Format currency value with RM symbol
 * @param {number|string} amount - The amount to format
 * @returns {string} - Formatted currency string with RM symbol
 */
export function formatRM(amount) {
  return formatCurrency(amount, 'RM', true);
}

/**
 * Format currency for display in tables and lists
 * @param {number|string} amount - The amount to format
 * @returns {string} - Formatted currency string without symbol
 */
export function formatAmount(amount) {
  return formatCurrency(amount, '', false);
}

/**
 * Parse and validate currency input
 * @param {string|number} input - The input value
 * @returns {number} - Parsed numeric value
 */
export function parseCurrency(input) {
  if (typeof input === 'number') return input;
  if (typeof input === 'string') {
    // Remove currency symbols and whitespace
    const cleaned = input.replace(/[RM$,\s]/g, '');
    const parsed = parseFloat(cleaned);
    return isNaN(parsed) ? 0 : parsed;
  }
  return 0;
}

/**
 * Format currency for input fields (no symbol, 2 decimal places)
 * @param {number|string} amount - The amount to format
 * @returns {string} - Formatted amount for input fields
 */
export function formatForInput(amount) {
  const numericAmount = parseFloat(amount || 0);
  return numericAmount > 0 ? numericAmount.toFixed(2) : '';
}

/**
 * Calculate total from array of amounts
 * @param {Array} amounts - Array of amount values
 * @returns {string} - Formatted total with 2 decimal places
 */
export function calculateTotal(amounts) {
  const total = amounts.reduce((sum, amount) => {
    return sum + parseFloat(amount || 0);
  }, 0);
  return total.toFixed(2);
} 