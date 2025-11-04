<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('device_tokens', 'platform')) {
                $table->string('platform')->nullable()->after('token'); // android|ios|web
            }
            if (!Schema::hasColumn('device_tokens', 'channel')) {
                $table->string('channel')->nullable()->after('platform');
            }
            if (!Schema::hasColumn('device_tokens', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('last_used_at');
            }
        });

        // Índice compuesto para acelerar consultas por (user_id, platform)
        Schema::table('device_tokens', function (Blueprint $table) {
            try {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_change_key_case($sm->listTableIndexes('device_tokens'));
                if (!array_key_exists('device_tokens_user_id_platform_index', $indexes)) {
                    $table->index(['user_id', 'platform'], 'device_tokens_user_id_platform_index');
                }
            } catch (\Throwable $e) {
                // Si Doctrine no está disponible, intentar crear el índice de todas formas
                try {
                    $table->index(['user_id', 'platform'], 'device_tokens_user_id_platform_index');
                } catch (\Throwable $ignored) {}
            }
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('device_tokens', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('device_tokens', 'channel')) {
                $table->dropColumn('channel');
            }
            if (Schema::hasColumn('device_tokens', 'platform')) {
                $table->dropColumn('platform');
            }
        });
    }
};
