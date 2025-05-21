// Simple date formatting utility for clinic-management-system
// Usage: formatDate('2025-05-18T00:52:36+08:00') => '2025-05-18'

export function formatDate(date) {
  if (!date) return '';
  const d = new Date(date);
  if (isNaN(d)) return '' + date;
  // Format as YYYY-MM-DD
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
