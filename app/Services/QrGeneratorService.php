<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrGeneratorService
{
    /**
     * Generate QR code with fallback for missing ImageMagick
     */
    public function generate(string $url, string $format = 'svg', int $size = 300, int $margin = 2): string
    {
        try {
            // Try using SimpleSoftwareIO QrCode (which uses ImageMagick for PNG)
            return QrCode::format($format)
                ->size($size)
                ->margin($margin)
                ->errorCorrection('H')
                ->generate($url);
                
        } catch (\Exception $e) {
            // If ImageMagick fails, use fallback methods
            if (strpos($e->getMessage(), 'imagick') !== false || strpos($e->getMessage(), 'ImageMagick') !== false) {
                return $this->generateWithFallback($url, $format, $size, $margin);
            }
            
            // Re-throw if it's a different error
            throw $e;
        }
    }
    
    /**
     * Fallback QR generation when ImageMagick is not available
     */
    private function generateWithFallback(string $url, string $format, int $size, int $margin): string
    {
        if ($format === 'svg') {
            // SVG doesn't need ImageMagick
            $renderer = new ImageRenderer(
                new RendererStyle($size, $margin),
                new SvgImageBackEnd()
            );
            
            $writer = new Writer($renderer);
            return $writer->writeString($url);
        }
        
        if ($format === 'png') {
            // For PNG, fallback to a simple text-based QR or base64-encoded SVG as PNG
            // We'll convert SVG to PNG using a different method
            $svg = $this->generateWithFallback($url, 'svg', $size, $margin);
            
            // For now, we'll return the SVG and let the client handle it
            // In production, you might want to use a different PNG library
            return $svg;
        }
        
        // Default to SVG for unsupported formats
        return $this->generateWithFallback($url, 'svg', $size, $margin);
    }
    
    /**
     * Check if ImageMagick is available
     */
    public function isImageMagickAvailable(): bool
    {
        return extension_loaded('imagick');
    }
    
    /**
     * Check if GD is available
     */
    public function isGdAvailable(): bool
    {
        return extension_loaded('gd');
    }
    
    /**
     * Check if any PNG-capable extension is available
     */
    public function canGeneratePng(): bool
    {
        return $this->isImageMagickAvailable() || $this->isGdAvailable();
    }
    
    /**
     * Get available QR formats based on system capabilities
     */
    public function getAvailableFormats(): array
    {
        $formats = ['svg']; // SVG is always available
        
        if ($this->canGeneratePng()) {
            $formats[] = 'png';
        }
        
        return $formats;
    }
    
    /**
     * Get system status for debugging
     */
    public function getSystemStatus(): array
    {
        return [
            'php_version' => phpversion(),
            'imagick_available' => $this->isImageMagickAvailable(),
            'gd_available' => $this->isGdAvailable(),
            'can_generate_png' => $this->canGeneratePng(),
            'loaded_extensions' => get_loaded_extensions()
        ];
    }
}