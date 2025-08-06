<?php

namespace App\Service;

use Symfony\Component\Asset\Packages;

class AssetService
{
    private $packages;
    private $projectDir;

    public function __construct(Packages $packages, string $projectDir)
    {
        $this->packages = $packages;
        $this->projectDir = $projectDir;
    }

    /**
     * Get webpack assets dynamically from shared volume or local fallback
     * Implements microservices asset synchronization
     */
    public function getWebpackAssets(): array
    {
        // Try shared volume first (updated by frontend container)
        $sharedEntrypointsFile = '/shared/build-assets/entrypoints.json';
        $localEntrypointsFile = $this->projectDir . '/public/build/entrypoints.json';
        
        $entrypointsFile = null;
        $source = 'fallback';
        
        if (file_exists($sharedEntrypointsFile)) {
            $entrypointsFile = $sharedEntrypointsFile;
            $source = 'shared';
        } elseif (file_exists($localEntrypointsFile)) {
            $entrypointsFile = $localEntrypointsFile;
            $source = 'local';
        }
        
        if (!$entrypointsFile) {
            error_log('[AssetService] No entrypoints.json found, using fallback assets');
            return $this->getFallbackAssets();
        }
        
        error_log('[AssetService] Loading assets from: ' . $source);

        $entrypoints = json_decode(file_get_contents($entrypointsFile), true);
        
        if (!isset($entrypoints['entrypoints']['app'])) {
            return $this->getFallbackAssets();
        }

        $assets = $entrypoints['entrypoints']['app'];
        
        // Convert absolute URLs to relative paths - production ready
        if (isset($assets['js'])) {
            $assets['js'] = array_map([$this, 'normalizeAssetUrl'], $assets['js']);
        }
        
        if (isset($assets['css'])) {
            $assets['css'] = array_map([$this, 'normalizeAssetUrl'], $assets['css']);
        }
        
        // If using shared assets, sync them to local public directory for direct serving
        if ($source === 'shared') {
            $this->syncSharedAssets();
        }

        // DEVELOPMENT FIX: Verify files actually exist, fallback if they don't
        if ($this->isDevelopment()) {
            $assets = $this->verifyAndFixAssets($assets);
        }

        return $assets;
    }

    /**
     * Check if we're in development mode
     */
    private function isDevelopment(): bool
    {
        return $_ENV['APP_ENV'] === 'dev' || $_ENV['APP_ENV'] === 'development';
    }

    /**
     * Verify assets exist and fix mismatches in development
     */
    private function verifyAndFixAssets(array $assets): array
    {
        $buildDir = $this->projectDir . '/public/build';
        
        // Check JS files
        if (isset($assets['js'])) {
            $fixedJs = [];
            foreach ($assets['js'] as $jsFile) {
                $filePath = $this->projectDir . '/public' . $jsFile;
                if (file_exists($filePath)) {
                    $fixedJs[] = $jsFile;
                } else {
                    // Try to find the actual file with different hash
                    $fixedFile = $this->findActualFile($jsFile, $buildDir);
                    if ($fixedFile) {
                        $fixedJs[] = $fixedFile;
                    }
                }
            }
            $assets['js'] = $fixedJs;
        }

        // If we couldn't fix JS files, use fallback
        if (empty($assets['js'])) {
            $fallback = $this->getFallbackAssets();
            $assets['js'] = $fallback['js'];
        }

        return $assets;
    }

    /**
     * Find the actual file when webpack manifest is out of sync
     */
    private function findActualFile(string $expectedFile, string $buildDir): ?string
    {
        $basename = basename($expectedFile);
        $pattern = preg_replace('/-[a-f0-9]{6}\.js$/', '-*.js', $basename);
        
        $files = glob($buildDir . '/' . $pattern);
        if (!empty($files)) {
            $actualFile = basename($files[0]);
            return '/build/' . $actualFile;
        }
        
        return null;
    }

    /**
     * Fallback method that scans build directory for assets
     */
    private function getFallbackAssets(): array
    {
        $buildDir = $this->projectDir . '/public/build';
        
        if (!is_dir($buildDir)) {
            return ['js' => [], 'css' => []];
        }

        $files = scandir($buildDir);
        $jsFiles = [];
        $cssFiles = [];

        // Find runtime.js first
        foreach ($files as $file) {
            if ($file === 'runtime.js') {
                $jsFiles[] = '/build/' . $file;
                break;
            }
        }

        // Find vendor files
        foreach ($files as $file) {
            if (strpos($file, 'vendors-') === 0 && substr($file, -3) === '.js') {
                $jsFiles[] = '/build/' . $file;
            }
        }

        // Find app.js
        foreach ($files as $file) {
            if ($file === 'app.js') {
                $jsFiles[] = '/build/' . $file;
            }
        }

        // Find CSS files
        foreach ($files as $file) {
            if ($file === 'app.css') {
                $cssFiles[] = '/build/' . $file;
            }
        }

        return [
            'js' => $jsFiles,
            'css' => $cssFiles
        ];
    }
    
    /**
     * Sync assets from shared volume to local public directory
     * This ensures assets are available for direct serving by nginx
     */
    private function syncSharedAssets(): void
    {
        $sharedDir = '/shared/build-assets';
        $localDir = $this->projectDir . '/public/build';
        
        if (!is_dir($sharedDir)) {
            return;
        }
        
        // Create local build directory if it doesn't exist
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }
        
        // Get modification times to avoid unnecessary copies
        $sharedModTime = filemtime($sharedDir . '/entrypoints.json');
        $localModTime = file_exists($localDir . '/entrypoints.json') 
            ? filemtime($localDir . '/entrypoints.json') 
            : 0;
            
        // Only sync if shared assets are newer
        if ($sharedModTime <= $localModTime) {
            return;
        }
        
        error_log('[AssetService] Syncing assets from shared volume to local directory');
        
        // Copy all files from shared to local
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sharedDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $localDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
        
        error_log('[AssetService] Asset sync completed');
    }
    
    /**
     * Normalize asset URLs to be relative paths suitable for any domain
     * Removes any hardcoded localhost references and ensures proper relative paths
     */
    private function normalizeAssetUrl(string $url): string
    {
        // Remove any absolute URL prefixes (localhost, webpack dev server, etc.)
        $patterns = [
            '/^https?:\/\/localhost(:[0-9]+)?\//',
            '/^https?:\/\/[^:\/]+:[0-9]+\//',  // Any host:port combination
            '/^https?:\/\/[^:\/]+\//'           // Any host without port
        ];
        
        foreach ($patterns as $pattern) {
            $url = preg_replace($pattern, '/', $url);
        }
        
        // Ensure URL starts with / for proper relative path
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }
        
        return $url;
    }
} 