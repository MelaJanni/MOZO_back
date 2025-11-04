<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * TokenManager - Gestión centralizada de tokens FCM
 *
 * Responsabilidad: Obtener, filtrar, agrupar y mantener tokens FCM
 * Elimina duplicación de lógica de tokens en múltiples servicios
 */
class TokenManager
{
    /**
     * Obtener tokens de un usuario específico
     *
     * @param int $userId
     * @param string|null $platform Filtrar por plataforma (android, ios, web)
     * @return array Array de tokens válidos
     */
    public function getUserTokens(int $userId, ?string $platform = null): array
    {
        try {
            $query = DeviceToken::where('user_id', $userId);

            // Filtrar por plataforma si se especifica
            if ($platform) {
                $query->where('platform', $platform);
            }

            // Solo tokens no expirados
            $query->where(function($q) {
                $q->where('expires_at', '>', now())
                  ->orWhereNull('expires_at');
            });

            $tokens = $query->pluck('token')->toArray();

            Log::debug('User tokens retrieved', [
                'user_id' => $userId,
                'platform' => $platform,
                'count' => count($tokens)
            ]);

            return $tokens;

        } catch (\Exception $e) {
            Log::error('Failed to get user tokens', [
                'user_id' => $userId,
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener tokens de administradores de un negocio
     *
     * @param int $businessId
     * @return array Array de tokens de admins
     */
    public function getBusinessAdminTokens(int $businessId): array
    {
        try {
            $tokens = DeviceToken::whereHas('user', function($query) use ($businessId) {
                $query->where('role', 'admin')
                      ->whereHas('businesses', function($b) use ($businessId) {
                          $b->where('business_id', $businessId)
                            ->where('business_admins.is_active', true);
                      });
            })
            // Solo tokens no expirados
            ->where(function($q) {
                $q->where('expires_at', '>', now())
                  ->orWhereNull('expires_at');
            })
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

            Log::debug('Business admin tokens retrieved', [
                'business_id' => $businessId,
                'count' => count($tokens)
            ]);

            return $tokens;

        } catch (\Exception $e) {
            Log::error('Failed to get business admin tokens', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Filtrar tokens expirados de una lista
     *
     * @param array $tokens Lista de tokens a filtrar
     * @return array Tokens válidos (no expirados)
     */
    public function filterExpiredTokens(array $tokens): array
    {
        if (empty($tokens)) {
            return [];
        }

        try {
            $validTokens = DeviceToken::whereIn('token', $tokens)
                ->where(function($q) {
                    $q->where('expires_at', '>', now())
                      ->orWhereNull('expires_at');
                })
                ->pluck('token')
                ->toArray();

            $filtered = count($tokens) - count($validTokens);

            if ($filtered > 0) {
                Log::info('Expired tokens filtered', [
                    'total' => count($tokens),
                    'valid' => count($validTokens),
                    'filtered' => $filtered
                ]);
            }

            return $validTokens;

        } catch (\Exception $e) {
            Log::error('Failed to filter expired tokens', [
                'error' => $e->getMessage()
            ]);
            // En caso de error, devolver tokens originales
            return $tokens;
        }
    }

    /**
     * Agrupar tokens por plataforma (web, android, ios)
     *
     * @param array $tokens Lista de tokens
     * @return array Array asociativo ['web' => [...], 'android' => [...], 'ios' => [...]]
     */
    public function groupByPlatform(array $tokens): array
    {
        if (empty($tokens)) {
            return ['web' => [], 'android' => [], 'ios' => []];
        }

        try {
            $grouped = DeviceToken::whereIn('token', $tokens)
                ->get()
                ->groupBy('platform')
                ->map(fn($group) => $group->pluck('token')->toArray())
                ->toArray();

            // Asegurar que todas las plataformas existan en el resultado
            return [
                'web' => $grouped['web'] ?? [],
                'android' => $grouped['android'] ?? [],
                'ios' => $grouped['ios'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Failed to group tokens by platform', [
                'error' => $e->getMessage()
            ]);
            return ['web' => [], 'android' => [], 'ios' => []];
        }
    }

    /**
     * Actualizar/registrar token de usuario
     * FIX CRÍTICO: No elimina múltiples dispositivos, usa updateOrCreate
     *
     * @param int $userId
     * @param string $token Token FCM
     * @param string $platform Plataforma (android, ios, web)
     * @return bool
     */
    public function refreshToken(int $userId, string $token, string $platform): bool
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                Log::error('User not found for token refresh', ['user_id' => $userId]);
                return false;
            }

            // FIX CRÍTICO: updateOrCreate en lugar de delete + create
            // Permite múltiples dispositivos por usuario en la misma plataforma
            DeviceToken::updateOrCreate(
                [
                    'user_id' => $userId,
                    'token' => $token,
                    'platform' => $platform
                ],
                [
                    'expires_at' => now()->addDays(60),
                    'last_used_at' => now()
                ]
            );

            Log::info('User token refreshed', [
                'user_id' => $userId,
                'platform' => $platform,
                'token_preview' => substr($token, 0, 20) . '...'
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to refresh user token', [
                'user_id' => $userId,
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Limpiar tokens expirados de la base de datos
     * Para uso en comando cron diario
     *
     * @return int Cantidad de tokens eliminados
     */
    public function cleanExpiredTokens(): int
    {
        try {
            // Eliminar tokens con expires_at en el pasado
            $deleted = DeviceToken::where('expires_at', '<', now())->delete();

            // Eliminar tokens muy antiguos sin fecha de expiración (>90 días)
            $oldDeleted = DeviceToken::whereNull('expires_at')
                ->where('created_at', '<', now()->subDays(90))
                ->delete();

            $totalDeleted = $deleted + $oldDeleted;

            Log::info('Expired tokens cleaned', [
                'expired_tokens' => $deleted,
                'old_tokens_without_expiry' => $oldDeleted,
                'total_deleted' => $totalDeleted
            ]);

            return $totalDeleted;

        } catch (\Exception $e) {
            Log::error('Failed to clean expired tokens', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Marcar token como inválido (para tokens que fallan en FCM)
     * Elimina tokens que devuelven 404/410 de FCM
     *
     * @param string $token
     * @return void
     */
    public function markTokenAsInvalid(string $token): void
    {
        try {
            $deleted = DeviceToken::where('token', $token)->delete();

            if ($deleted) {
                Log::info('Invalid token removed', [
                    'token_preview' => substr($token, 0, 20) . '...',
                    'reason' => 'FCM returned 404/410'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to mark token as invalid', [
                'token_preview' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de tokens
     *
     * @return array
     */
    public function getTokenStats(): array
    {
        try {
            return [
                'total' => DeviceToken::count(),
                'active' => DeviceToken::where(function($q) {
                    $q->where('expires_at', '>', now())
                      ->orWhereNull('expires_at');
                })->count(),
                'expired' => DeviceToken::where('expires_at', '<', now())->count(),
                'by_platform' => DeviceToken::groupBy('platform')
                    ->selectRaw('platform, count(*) as count')
                    ->pluck('count', 'platform')
                    ->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get token stats', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
