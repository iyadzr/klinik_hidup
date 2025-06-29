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
     * Get webpack assets dynamically without hardcoding filenames
     */
    public function getWebpackAssets(): array
    {
        $entrypointsFile = $this->projectDir . '/public/build/entrypoints.json';
        
        if (!file_exists($entrypointsFile)) {
            return $this->getFallbackAssets();
        }

        $entrypoints = json_decode(file_get_contents($entrypointsFile), true);
        
        if (!isset($entrypoints['entrypoints']['app'])) {
            return $this->getFallbackAssets();
        }

        $assets = $entrypoints['entrypoints']['app'];
        
        // Convert absolute URLs to relative paths for nginx proxy
        if (isset($assets['js'])) {
            $assets['js'] = array_map(function($url) {
                return str_replace('http://localhost:8080', '', $url);
            }, $assets['js']);
        }
        
        if (isset($assets['css'])) {
            $assets['css'] = array_map(function($url) {
                return str_replace('http://localhost:8080', '', $url);
            }, $assets['css']);
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
} 