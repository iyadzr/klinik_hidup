/**
 * Currency Formatting Mixin
 * Provides consistent currency formatting methods to Vue components
 */

export const currencyMixin = {
  methods: {
    /**
     * Format currency value to always show 2 decimal places
     * @param {number|string} amount - The amount to format
     * @returns {string} - Formatted currency string
     */
    formatCurrency(amount) {
      const numericAmount = parseFloat(amount || 0);
      return numericAmount.toFixed(2);
    },

    /**
     * Format currency value with RM symbol
     * @param {number|string} amount - The amount to format
     * @returns {string} - Formatted currency string with RM symbol
     */
    formatRM(amount) {
      const formatted = this.formatCurrency(amount);
      return `RM ${formatted}`;
    },

    /**
     * Format currency for input fields (no symbol, 2 decimal places)
     * @param {number|string} amount - The amount to format
     * @returns {string} - Formatted amount for input fields
     */
    formatForInput(amount) {
      const numericAmount = parseFloat(amount || 0);
      return numericAmount > 0 ? numericAmount.toFixed(2) : '';
    },

    /**
     * Parse and validate currency input
     * @param {string|number} input - The input value
     * @returns {number} - Parsed numeric value
     */
    parseCurrency(input) {
      if (typeof input === 'number') return input;
      if (typeof input === 'string') {
        // Remove currency symbols and whitespace
        const cleaned = input.replace(/[RM$,\s]/g, '');
        const parsed = parseFloat(cleaned);
        return isNaN(parsed) ? 0 : parsed;
      }
      return 0;
    },

    /**
     * Calculate total from array of amounts
     * @param {Array} amounts - Array of amount values
     * @returns {string} - Formatted total with 2 decimal places
     */
    calculateTotal(amounts) {
      const total = amounts.reduce((sum, amount) => {
        return sum + parseFloat(amount || 0);
      }, 0);
      return total.toFixed(2);
    },

    /**
     * Format amount for display (legacy method for backward compatibility)
     * @param {number|string} amount - The amount to format
     * @returns {string} - Formatted amount
     */
    formatAmount(amount) {
      return this.formatCurrency(amount);
    }
  }
}; 