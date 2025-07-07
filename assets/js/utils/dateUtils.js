// Standardized date formatting utility for clinic-management-system
// All dates formatted in DD/MM/YYYY format for Malaysian standard

/**
 * Format date in DD/MM/YYYY format
 * @param {string|Date} dateString - Date to format
 * @returns {string} - Formatted date in DD/MM/YYYY format
 */
export function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB', {
    timeZone: 'Asia/Kuala_Lumpur',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

/**
 * Format date with full month name in DD Month YYYY format
 * @param {string|Date} date - Date to format
 * @returns {string} - Formatted date in DD Month YYYY format
 */
export const formatDateOnly = (date) => {
  if (!date) return 'N/A';
  try {
    const d = new Date(date);
    return d.toLocaleDateString('en-GB', {
      timeZone: 'Asia/Kuala_Lumpur',
      day: '2-digit',
      month: 'long',
      year: 'numeric'
    });
  } catch (e) {
    console.error('Error formatting date:', e);
    return 'Invalid Date';
  }
};

/**
 * Format datetime in DD/MM/YYYY, HH:MM format
 * @param {string|Date} dateString - Date to format
 * @returns {string} - Formatted datetime
 */
export function formatDateTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('en-GB', {
    timeZone: 'Asia/Kuala_Lumpur',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  });
}

// Get today's date in MYT timezone in YYYY-MM-DD format (for API calls)
export const getTodayInMYT = () => {
  try {
    const now = new Date();
    
    // Use Intl.DateTimeFormat to get the correct date in MYT timezone
    const formatter = new Intl.DateTimeFormat('en-CA', {
      timeZone: 'Asia/Kuala_Lumpur',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });
    
    const dateString = formatter.format(now);
    console.log('🕐 Current MYT date:', dateString, 'Local time:', now.toLocaleString(), 'MYT time:', now.toLocaleString('en-GB', { timeZone: 'Asia/Kuala_Lumpur' }));
    return dateString;
  } catch (e) {
    console.error('Error getting today in MYT:', e);
    // Fallback to local date
    return new Date().toISOString().split('T')[0];
  }
};

export function formatQueueNumber(queueNumber) {
  if (!queueNumber) return '';
  queueNumber = queueNumber.toString();
  if (queueNumber.length === 4) return queueNumber;
  if (queueNumber.length === 3) return '0' + queueNumber;
  if (queueNumber.length < 3) return queueNumber.padStart(4, '0');
  return queueNumber;
}
