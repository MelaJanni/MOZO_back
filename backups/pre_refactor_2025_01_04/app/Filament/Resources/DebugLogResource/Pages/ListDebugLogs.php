<?php

namespace App\Filament\Resources\DebugLogResource\Pages;

use App\Filament\Resources\DebugLogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\File;
use Filament\Notifications\Notification;

class ListDebugLogs extends ListRecords
{
    protected static string $resource = DebugLogResource::class;

    protected ?string $maxContentWidth = MaxWidth::Full->value;

    public function getTitle(): string
    {
        return '🔍 Debug & Logs del Sistema';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_logs')
                ->label('🔄 Refrescar Logs')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    Notification::make()
                        ->title('Logs actualizados')
                        ->success()
                        ->send();

                    $this->redirect(request()->header('Referer'));
                }),

            Actions\Action::make('clear_logs')
                ->label('🗑️ Limpiar Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $logFile = storage_path('logs/laravel.log');
                    file_put_contents($logFile, "=== LOGS CLEARED BY ADMIN AT " . now() . " ===\n");

                    Notification::make()
                        ->title('Logs limpiados exitosamente')
                        ->success()
                        ->send();

                    $this->redirect(request()->header('Referer'));
                }),

            Actions\Action::make('test_502')
                ->label('🧪 Test 502 Debug')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('warning')
                ->url('/debug-502-test', shouldOpenInNewTab: true),
        ];
    }

    public function getViewData(): array
    {
        return [
            'logContent' => $this->getLogContent(),
            'systemInfo' => $this->getSystemInfo(),
            'recentErrors' => $this->getRecentErrors(),
        ];
    }

    protected function getLogContent(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return [
                'exists' => false,
                'lines' => ['Log file not found'],
                'size' => 0,
                'modified' => null
            ];
        }

        $lines = file($logFile);
        $lastLines = array_slice($lines, -100); // Últimas 100 líneas

        return [
            'exists' => true,
            'lines' => $lastLines,
            'size' => filesize($logFile),
            'modified' => filemtime($logFile),
            'totalLines' => count($lines)
        ];
    }

    protected function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'database_connection' => config('database.default'),
        ];
    }

    protected function getRecentErrors(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile);
        $errors = [];

        foreach (array_reverse(array_slice($lines, -200)) as $line) {
            if (strpos($line, '.ERROR:') !== false ||
                strpos($line, 'Exception') !== false ||
                strpos($line, 'error') !== false) {
                $errors[] = trim($line);
                if (count($errors) >= 20) break; // Máximo 20 errores
            }
        }

        return array_reverse($errors);
    }

    protected static string $view = 'filament.resources.debug-log-resource.pages.list-debug-logs';
}