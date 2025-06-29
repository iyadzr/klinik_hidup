<?php

namespace App\Twig;

use App\Service\AssetService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExtension extends AbstractExtension
{
    private $assetService;

    public function __construct(AssetService $assetService)
    {
        $this->assetService = $assetService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('webpack_js_assets', [$this, 'getJsAssets']),
            new TwigFunction('webpack_css_assets', [$this, 'getCssAssets']),
        ];
    }

    public function getJsAssets(): array
    {
        $assets = $this->assetService->getWebpackAssets();
        return $assets['js'] ?? [];
    }

    public function getCssAssets(): array
    {
        $assets = $this->assetService->getWebpackAssets();
        return $assets['css'] ?? [];
    }
} 