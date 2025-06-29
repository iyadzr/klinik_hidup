// Simple date formatting utility for clinic-management-system
// Usage: formatDate('2025-05-18T00:52:36+08:00') => '2025-05-18'

export function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('ms-MY', {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric'
  });
}

export const formatDateOnly = (date) => {
  if (!date) return 'N/A';
  try {
    const d = new Date(date);
    return d.toLocaleDateString('en-MY', {
      timeZone: 'Asia/Kuala_Lumpur',
      year: 'numeric',
      month: 'long',
      day: '2-digit'
    });
  } catch (e) {
    console.error('Error formatting date:', e);
    return 'Invalid Date';
  }
};

// Get today's date in MYT timezone in YYYY-MM-DD format
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
    console.log('🕐 Current MYT date:', dateString, 'Local time:', now.toLocaleString(), 'MYT time:', now.toLocaleString('en-MY', { timeZone: 'Asia/Kuala_Lumpur' }));
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
