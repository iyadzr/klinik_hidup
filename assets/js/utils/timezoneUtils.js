/**
 * Timezone utilities for Malaysia timezone handling
 */

export const MALAYSIA_TIMEZONE = 'Asia/Kuala_Lumpur';

/**
 * Format date for Malaysia timezone display
 * @param {Date|string} date - Date to format
 * @param {object} options - Intl.DateTimeFormat options
 * @returns {string} - Formatted date string
 */
export function formatDateMalaysia(date, options = {}) {
    const defaultOptions = {
        timeZone: MALAYSIA_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        ...options
    };
    
    return new Date(date).toLocaleString('en-MY', defaultOptions);
}

/**
 * Format date only (no time) for Malaysia timezone
 * @param {Date|string} date - Date to format
 * @returns {string} - Formatted date string (YYYY-MM-DD)
 */
export function formatDateOnlyMalaysia(date) {
    return new Date(date).toLocaleDateString('en-CA', {
        timeZone: MALAYSIA_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

/**
 * Format time only for Malaysia timezone
 * @param {Date|string} date - Date to format
 * @returns {string} - Formatted time string (HH:MM AM/PM)
 */
export function formatTimeOnlyMalaysia(date) {
    return new Date(date).toLocaleTimeString('en-US', {
        timeZone: MALAYSIA_TIMEZONE,
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

/**
 * Get current date/time in Malaysia timezone
 * @returns {Date} - Current date in Malaysia timezone
 */
export function nowInMalaysia() {
    // Get current UTC time
    const now = new Date();
    
    // Convert to Malaysia timezone (UTC+8)
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const malaysiaTime = new Date(utc + (8 * 3600000));
    
    return malaysiaTime;
}

/**
 * Get start of day in Malaysia timezone
 * @param {Date|string} date - Date (optional, defaults to today)
 * @returns {Date} - Start of day in Malaysia timezone
 */
export function startOfDayMalaysia(date = null) {
    const targetDate = date ? new Date(date) : nowInMalaysia();
    const malaysiaDate = new Date(targetDate.toLocaleString('en-US', { timeZone: MALAYSIA_TIMEZONE }));
    malaysiaDate.setHours(0, 0, 0, 0);
    return malaysiaDate;
}

/**
 * Get end of day in Malaysia timezone
 * @param {Date|string} date - Date (optional, defaults to today)
 * @returns {Date} - End of day in Malaysia timezone
 */
export function endOfDayMalaysia(date = null) {
    const targetDate = date ? new Date(date) : nowInMalaysia();
    const malaysiaDate = new Date(targetDate.toLocaleString('en-US', { timeZone: MALAYSIA_TIMEZONE }));
    malaysiaDate.setHours(23, 59, 59, 999);
    return malaysiaDate;
}

/**
 * Convert any date to Malaysia timezone
 * @param {Date|string} date - Date to convert
 * @returns {Date} - Date converted to Malaysia timezone
 */
export function toMalaysiaTimezone(date) {
    return new Date(new Date(date).toLocaleString('en-US', { timeZone: MALAYSIA_TIMEZONE }));
}

/**
 * Format for display with Malaysia timezone label
 * @param {Date|string} date - Date to format
 * @param {boolean} includeSeconds - Whether to include seconds
 * @returns {string} - Formatted date with timezone label
 */
export function formatWithTimezone(date, includeSeconds = false) {
    const options = {
        timeZone: MALAYSIA_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    };
    
    if (includeSeconds) {
        options.second = '2-digit';
    }
    
    const formatted = new Date(date).toLocaleString('en-MY', options);
    return `${formatted} (MYT)`;
}

/**
 * Get timezone offset for Malaysia (+08:00)
 * @returns {string} - Timezone offset string
 */
export function getMalaysiaTimezoneOffset() {
    return '+08:00';
}

/**
 * Check if a date is today in Malaysia timezone
 * @param {Date|string} date - Date to check
 * @returns {boolean} - Whether the date is today in Malaysia timezone
 */
export function isTodayMalaysia(date) {
    const today = formatDateOnlyMalaysia(nowInMalaysia());
    const targetDate = formatDateOnlyMalaysia(date);
    return today === targetDate;
}

/**
 * Get days difference from today in Malaysia timezone
 * @param {Date|string} date - Date to compare
 * @returns {number} - Days difference (positive for future, negative for past)
 */
export function daysDifferenceFromTodayMalaysia(date) {
    const today = startOfDayMalaysia();
    const targetDate = startOfDayMalaysia(date);
    const timeDiff = targetDate.getTime() - today.getTime();
    return Math.ceil(timeDiff / (1000 * 3600 * 24));
}

// Default export with all utilities
export default {
    MALAYSIA_TIMEZONE,
    formatDateMalaysia,
    formatDateOnlyMalaysia,
    formatTimeOnlyMalaysia,
    nowInMalaysia,
    startOfDayMalaysia,
    endOfDayMalaysia,
    toMalaysiaTimezone,
    formatWithTimezone,
    getMalaysiaTimezoneOffset,
    isTodayMalaysia,
    daysDifferenceFromTodayMalaysia
}; 