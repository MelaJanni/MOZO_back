<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions;

class DebugPanel extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static string $view = 'filament.pages.debug-panel';

    protected static ?string $navigationLabel = 'Debug Logs';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '🔍 Debug & Logs del Sistema';

    protected static string $routePath = '/debug-panel';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_logs')
                ->label('🔄 Refrescar')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
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

                    \Filament\Notifications\Notification::make()
                        ->title('Logs limpiados exitosamente')
                        ->success()
                        ->send();
                }),
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

    protected function getLogContent(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return [
                'exists' => false,
                'lines' => ['Log file not found'],
                'size' => 0,
                'modified' => null,
                'totalLines' => 0
            ];
        }

        $lines = file($logFile);
        $lastLines = array_slice($lines, -100);

        return [
            'exists' => true,
            'lines' => $lastLines,
            'size' => filesize($logFile),
            'modified' => filemtime($logFile),
            'totalLines' => count($lines)
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
                if (count($errors) >= 20) break;
            }
        }

        return array_reverse($errors);
    }

    public static function canAccess(): bool
    {
        return true; // Permitir acceso a todos los usuarios autenticados
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true; // Mostrar en navegación
    }
}