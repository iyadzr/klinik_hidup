// Simple date formatting utility for clinic-management-system
// Usage: formatDate('2025-05-18T00:52:36+08:00') => '2025-05-18'

export const formatDate = (date) => {
  if (!date) return 'N/A';
  try {
    const d = new Date(date);
    return d.toLocaleDateString('en-MY', {
      timeZone: 'Asia/Kuala_Lumpur',
      year: 'numeric',
      month: 'long',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    });
  } catch (e) {
    console.error('Error formatting date:', e);
    return 'Invalid Date';
  }
};

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
