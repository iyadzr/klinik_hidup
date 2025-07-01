<?php

namespace App\Service;

class TimezoneService
{
    public const MALAYSIA_TIMEZONE = 'Asia/Kuala_Lumpur';
    
    private static ?\DateTimeZone $malaysiaTimezone = null;
    private static ?\DateTimeZone $utcTimezone = null;
    
    public static function getMalaysiaTimezone(): \DateTimeZone
    {
        if (self::$malaysiaTimezone === null) {
            self::$malaysiaTimezone = new \DateTimeZone(self::MALAYSIA_TIMEZONE);
        }
        
        return self::$malaysiaTimezone;
    }
    
    public static function getUtcTimezone(): \DateTimeZone
    {
        if (self::$utcTimezone === null) {
            self::$utcTimezone = new \DateTimeZone('UTC');
        }
        
        return self::$utcTimezone;
    }
    
    /**
     * Create a new DateTime object in Malaysia timezone
     */
    public static function now(): \DateTime
    {
        return new \DateTime('now', self::getMalaysiaTimezone());
    }
    
    /**
     * Create a new DateTimeImmutable object in Malaysia timezone
     */
    public static function nowImmutable(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', self::getMalaysiaTimezone());
    }
    
    /**
     * Create a DateTime object for a specific date/time in Malaysia timezone
     */
    public static function createDateTime(string $datetime): \DateTime
    {
        return new \DateTime($datetime, self::getMalaysiaTimezone());
    }
    
    /**
     * Create a DateTimeImmutable object for a specific date/time in Malaysia timezone
     */
    public static function createDateTimeImmutable(string $datetime): \DateTimeImmutable
    {
        return new \DateTimeImmutable($datetime, self::getMalaysiaTimezone());
    }
    
    /**
     * Convert a DateTime to Malaysia timezone
     */
    public static function convertToMalaysia(\DateTimeInterface $dateTime): \DateTime
    {
        $converted = \DateTime::createFromInterface($dateTime);
        $converted->setTimezone(self::getMalaysiaTimezone());
        return $converted;
    }
    
    /**
     * Convert a DateTime to UTC timezone
     */
    public static function convertToUtc(\DateTimeInterface $dateTime): \DateTime
    {
        $converted = \DateTime::createFromInterface($dateTime);
        $converted->setTimezone(self::getUtcTimezone());
        return $converted;
    }
    
    /**
     * Create start of day in Malaysia timezone
     */
    public static function startOfDay(?string $date = null): \DateTime
    {
        $dateString = $date ? $date . ' 00:00:00' : 'today 00:00:00';
        return new \DateTime($dateString, self::getMalaysiaTimezone());
    }
    
    /**
     * Create end of day in Malaysia timezone
     */
    public static function endOfDay(?string $date = null): \DateTime
    {
        $dateString = $date ? $date . ' 23:59:59' : 'today 23:59:59';
        return new \DateTime($dateString, self::getMalaysiaTimezone());
    }
    
    /**
     * Get timezone offset string for frontend use
     */
    public static function getOffsetString(): string
    {
        $now = self::now();
        return $now->format('P'); // Returns +08:00 for Malaysia
    }
    
    /**
     * Format datetime for display in Malaysia timezone
     */
    public static function formatForDisplay(\DateTimeInterface $dateTime, string $format = 'Y-m-d H:i:s'): string
    {
        $malaysiaTime = self::convertToMalaysia($dateTime);
        return $malaysiaTime->format($format);
    }
} 