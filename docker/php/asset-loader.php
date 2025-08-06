<?php
/**
 * Dynamic Asset Loader for Microservices Architecture
 * Automatically loads the latest frontend assets from shared volume
 */

class AssetLoader 
{
    private const MANIFEST_PATH = '/var/www/html/public/build/entrypoints.json';
    private const SHARED_MANIFEST_PATH = '/shared/build-assets/entrypoints.json';
    private const CACHE_TIME = 30; // seconds
    
    private static $manifestCache = null;
    private static $cacheTime = 0;
    
    /**
     * Get the latest asset manifest from shared volume or fallback to local
     */
    public static function getManifest(): array 
    {
        // Check cache first
        if (self::$manifestCache !== null && (time() - self::$cacheTime) < self::CACHE_TIME) {
            return self::$manifestCache;
        }
        
        $manifest = [];
        
        // Try to load from shared volume first (latest from frontend container)
        if (file_exists(self::SHARED_MANIFEST_PATH)) {
            $content = file_get_contents(self::SHARED_MANIFEST_PATH);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if ($decoded !== null) {
                    $manifest = $decoded;
                    error_log("[Asset Loader] Loaded manifest from shared volume");
                }
            }
        }
        
        // Fallback to local manifest if shared is not available
        if (empty($manifest) && file_exists(self::MANIFEST_PATH)) {
            $content = file_get_contents(self::MANIFEST_PATH);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if ($decoded !== null) {
                    $manifest = $decoded;
                    error_log("[Asset Loader] Loaded manifest from local build");
                }
            }
        }
        
        // Cache the result
        self::$manifestCache = $manifest;
        self::$cacheTime = time();
        
        return $manifest;
    }
    
    /**
     * Get CSS assets for a specific entry point
     */
    public static function getCssAssets(string $entrypoint = 'app'): array 
    {
        $manifest = self::getManifest();
        
        if (isset($manifest['entrypoints'][$entrypoint]['css'])) {
            return $manifest['entrypoints'][$entrypoint]['css'];
        }
        
        return [];
    }
    
    /**
     * Get JS assets for a specific entry point
     */
    public static function getJsAssets(string $entrypoint = 'app'): array 
    {
        $manifest = self::getManifest();
        
        if (isset($manifest['entrypoints'][$entrypoint]['js'])) {
            return $manifest['entrypoints'][$entrypoint]['js'];
        }
        
        return [];
    }
    
    /**
     * Check if assets are available from shared volume
     */
    public static function isSharedAssetsAvailable(): bool 
    {
        return file_exists(self::SHARED_MANIFEST_PATH);
    }
    
    /**
     * Sync assets from shared volume to local public directory
     */
    public static function syncAssets(): bool 
    {
        $sharedDir = '/shared/build-assets';
        $localDir = '/var/www/html/public/build';
        
        if (!is_dir($sharedDir)) {
            return false;
        }
        
        // Create local build directory if it doesn't exist
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }
        
        // Copy all files from shared to local
        $success = true;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sharedDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $localDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                if (!copy($item, $target)) {
                    $success = false;
                    error_log("[Asset Loader] Failed to copy: " . $item . " to " . $target);
                } else {
                    error_log("[Asset Loader] Copied: " . $iterator->getSubPathName());
                }
            }
        }
        
        if ($success) {
            error_log("[Asset Loader] Asset synchronization completed successfully");
        }
        
        return $success;
    }
}