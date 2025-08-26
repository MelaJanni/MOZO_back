<?php

namespace App\Services;

use App\Models\QrCode;
use App\Models\Table;
use Hashids\Hashids;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Genera o actualiza el QR de una mesa y retorna el modelo.
     */
    public function generateForTable(Table $table, bool $forceRegenerate = false): QrCode
    {
        $business = $table->business;
        $tableCode = $table->code;
        if ($forceRegenerate) {
            // Generar un nuevo código único aleatorio y actualizar la mesa
            do {
                $candidate = Str::random(6);
            } while (Table::where('code', $candidate)->exists());
            $tableCode = $candidate;
            $table->update(['code' => $tableCode]);
        } elseif (!$tableCode) {
            $hashids = new Hashids(config('app.key'), 6);
            $tableCode = $hashids->encode($table->id);
            $table->update(['code' => $tableCode]);
        }
        $slug = $business->slug;
        $baseUrl = config('app.frontend_url', 'https://mozoqr.com');
        $qrUrl = rtrim($baseUrl, '/') . "/qr/{$slug}/{$tableCode}";
        return QrCode::updateOrCreate(
            ['table_id' => $table->id],
            [
                'business_id' => $business->id,
                'code'        => $tableCode,
                'url'         => $qrUrl,
            ]
        );
    }
}
