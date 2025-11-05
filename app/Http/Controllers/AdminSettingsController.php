<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminSettingsController extends Controller
{
    /**
     * Obtener configuración del negocio
     * 
     * Endpoint: GET /api/admin/settings
     */
    public function getSettings(Request $request)
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $business = Business::findOrFail($request->business_id);
        
        return response()->json([
            'business' => $business,
            'settings' => [
                'name' => $business->name,
                'address' => $business->address,
                'phone' => $business->phone,
                'email' => $business->email,
                'logo' => $business->logo,
                'working_hours' => $business->working_hours,
                'notification_preferences' => $business->notification_preferences,
            ],
        ]);
    }

    /**
     * Actualizar configuración del negocio
     * 
     * Soporta múltiples formatos de payload:
     * - Campos directos: { name: "...", address: "..." }
     * - Anidados bajo 'business': { business: { name: "..." } }
     * - Anidados bajo 'settings': { settings: { name: "..." } }
     * 
     * Endpoints: 
     * - POST /api/admin/settings
     * - PUT /api/admin/settings
     * - PATCH /api/admin/settings
     * - PUT /api/admin/business/settings
     */
    public function updateSettings(Request $request)
    {
        // Validar tanto en nivel raíz como anidado bajo 'business' o 'settings'
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'business.name' => 'sometimes|string|max:255',
            'settings.name' => 'sometimes|string|max:255',

            'address' => 'sometimes|string|max:255',
            'business.address' => 'sometimes|string|max:255',
            'settings.address' => 'sometimes|string|max:255',

            'phone' => 'sometimes|string|max:20',
            'business.phone' => 'sometimes|string|max:20',
            'settings.phone' => 'sometimes|string|max:20',

            'email' => 'sometimes|email|max:255',
            'business.email' => 'sometimes|email|max:255',
            'settings.email' => 'sometimes|email|max:255',

            'description' => 'sometimes|string|max:500',
            'business.description' => 'sometimes|string|max:500',
            'settings.description' => 'sometimes|string|max:500',

            'logo' => 'sometimes|file|image|max:2048',
            'logo_base64' => 'sometimes|string',

            'working_hours' => 'sometimes|array',
            'settings.working_hours' => 'sometimes|array',

            'notification_preferences' => 'sometimes|array',
            'settings.notification_preferences' => 'sometimes|array',
        ]);

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $business = Business::findOrFail($request->business_id);

        // Helper para extraer valor desde raíz, business.* o settings.*
        $getVal = function (string $key) use ($request) {
            if ($request->has($key)) return $request->input($key);
            if ($request->has("business.$key")) return $request->input("business.$key");
            if ($request->has("settings.$key")) return $request->input("settings.$key");
            return null;
        };

        foreach (['name', 'address', 'phone', 'email', 'description'] as $field) {
            $val = $getVal($field);
            if ($val !== null) {
                $business->$field = $val;
            }
        }

        // Logo por archivo
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $business->logo = $path;
        }
        // Logo por base64
        if ($request->filled('logo_base64')) {
            try {
                $business->logo = $this->storeBase64Image($request->input('logo_base64'), $business->id);
            } catch (\Throwable $e) {
                // Ignorar error de logo base64 inválido; no bloquea el resto de cambios
            }
        }

        // Campos JSON de configuración
        $wh = $getVal('working_hours');
        if ($wh !== null) {
            $business->working_hours = $wh;
        }
        $np = $getVal('notification_preferences');
        if ($np !== null) {
            $business->notification_preferences = $np;
        }

        $business->save();
        $business->refresh();

        return response()->json([
            'message' => 'Configuración actualizada exitosamente',
            'business' => $business,
        ], 200, [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    /**
     * Helper privado: Guardar imagen base64 en storage
     */
    private function storeBase64Image(string $base64Image, int $businessId): string
    {
        if (!preg_match('/data:image\/(\w+);base64,/', $base64Image, $matches)) {
            throw new \Exception('Formato base64 no válido');
        }
        $extension = $matches[1];
        $imageData = substr($base64Image, strpos($base64Image, ',') + 1);
        $imageData = base64_decode($imageData);
        $filename = Str::uuid() . '.' . $extension;
        $path = 'avatars/' . $businessId . '/' . $filename;
        Storage::disk('public')->put($path, $imageData);
        return $path;
    }
}
