<?php

namespace App\Services;

use App\Models\QrCode;
use App\Models\Table;
use Hashids\Hashids;

class QrCodeService
{
    /**
     * Genera o actualiza el QR de una mesa y retorna el modelo.
     */
    public function generateForTable(Table $table): QrCode
    {
        $business = $table->business;
        $tableCode = $table->code;
        if (!$tableCode) {
            $hashids = new Hashids(config('app.key'), 6);
            $tableCode = $hashids->encode($table->id);
            $table->update(['code' => $tableCode]);
        }
        $slug = $business->slug;
        $baseUrl = config('app.frontend_url', 'https://mozoqr.com');
        $qrUrl = rtrim($baseUrl, '/') . "/QR/{$slug}/{$tableCode}";
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
