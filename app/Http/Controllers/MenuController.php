<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Controllers\Concerns\ResolvesActiveBusiness;

class MenuController extends Controller
{
    use ResolvesActiveBusiness;
    public function index(Request $request)
    {
        $businessId = $request->query('business_id');
        
        if ($businessId) {
            $menus = Menu::where('business_id', $businessId)->get();
        } else {
            $menus = Menu::all();
        }
        
        return response()->json($menus);
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'menu_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:51200', // 50MB
            'is_default' => 'boolean',
        ]);

        $path = $request->file('menu_file')->store('menus', 'public');

        if ($request->is_default) {
            Menu::where('business_id', $request->business_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $menu = Menu::create([
            'business_id' => $request->business_id,
            'file_path' => $path,
            'is_default' => $request->is_default ?? false,
        ]);

        return response()->json($menu, 201);
    }

    public function show(Menu $menu)
    {
        return response()->json($menu);
    }

    public function update(Request $request, Menu $menu)
    {
        $request->validate([
            'business_id' => 'exists:businesses,id',
            'menu_file' => 'file|mimes:pdf,jpg,jpeg,png|max:51200', // 50MB
            'is_default' => 'boolean',
        ]);

        $data = [];

        if ($request->has('business_id')) {
            $data['business_id'] = $request->business_id;
        }

        if ($request->has('is_default') && $request->is_default) {
            Menu::where('business_id', $menu->business_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            
            $data['is_default'] = true;
        } elseif ($request->has('is_default')) {
            $data['is_default'] = $request->is_default;
        }

        if ($request->hasFile('menu_file')) {
            if (Storage::disk('public')->exists($menu->file_path)) {
                Storage::disk('public')->delete($menu->file_path);
            }
            
            $path = $request->file('menu_file')->store('menus', 'public');
            $data['file_path'] = $path;
        }

        $menu->update($data);

        return response()->json($menu);
    }

    public function destroy(Menu $menu)
    {
        $this->authorizeBusinessOwnership($menu->business_id);

        $businessId = $menu->business_id;
        $menuCount = Menu::where('business_id', $businessId)->count();

        if ($menuCount <= 1) {
            return response()->json(['message' => 'No puedes eliminar el último menú.'], 422);
        }

        $newDefaultMenu = null;
        if ($menu->is_default) {
            $newDefaultMenu = Menu::where('business_id', $businessId)
                ->where('id', '!=', $menu->id)
                ->orderBy('display_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($newDefaultMenu) {
                $newDefaultMenu->is_default = true;
                $newDefaultMenu->save();
            }
        }

        if (Storage::disk('public')->exists($menu->file_path)) {
            Storage::disk('public')->delete($menu->file_path);
        }

        $menu->delete();

        if ($newDefaultMenu) {
            return response()->json([
                'message' => 'Menú eliminado. Se ha establecido "' . $newDefaultMenu->name . '" como el nuevo menú predeterminado.',
                'new_default_menu_id' => $newDefaultMenu->id,
            ]);
        }

        return response()->json(['message' => 'Menú eliminado correctamente.']);
    }

    public function fetchMenus()
    {
    $user = Auth::user();
    $activeBusinessId = $this->activeBusinessId($user, 'admin');
    $menus = Menu::where('business_id', $activeBusinessId)
            ->orderBy('display_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $menus->transform(function ($menu) {
            $menu->view_url = url('storage/' . $menu->file_path);
            return $menu;
        });
        
        return response()->json([
            'menus' => $menus,
            'count' => $menus->count(),
            'default_menu' => $menus->where('is_default', true)->first()
        ]);
    }
    
    public function uploadMenu(Request $request)
    {
        // 🚀 MANEJO MEJORADO DE ERRORES 413
        if (!$request->hasFile('file') && empty($request->all())) {
            return response()->json([
                'success' => false,
                'error' => 'REQUEST_TOO_LARGE',
                'message' => 'El archivo es demasiado grande para el servidor.',
                'details' => [
                    'max_allowed' => '50MB',
                    'common_causes' => [
                        'Archivo mayor a 50MB',
                        'Límites del servidor web',
                        'Configuración PHP restrictiva'
                    ]
                ],
                'diagnosis_url' => url('/api/menus/upload-limits')
            ], 413);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:51200', // 50MB (era 2MB)
            'is_default' => 'sometimes|boolean',
            'category' => 'sometimes|string|max:50',
            'description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            
            // Detectar errores relacionados con tamaño de archivo
            if ($errors->has('file')) {
                $fileErrors = $errors->get('file');
                foreach ($fileErrors as $error) {
                    if (strpos($error, 'may not be greater than') !== false) {
                        return response()->json([
                            'success' => false,
                            'error' => 'FILE_TOO_LARGE',
                            'message' => 'El archivo supera el límite de 50MB',
                            'errors' => $errors,
                            'diagnosis_url' => url('/api/menus/upload-limits')
                        ], 413);
                    }
                }
            }
            
            return response()->json(['errors' => $errors], 422);
        }
        
    $user = Auth::user();
    $activeBusinessId = $this->activeBusinessId($user, 'admin');
    $path = $request->file('file')->store('menus/' . $activeBusinessId, 'public');
        
        if ($request->has('is_default') && $request->is_default) {
            Menu::where('business_id', $activeBusinessId)
                ->update(['is_default' => false]);
        }
        
    $maxOrder = Menu::where('business_id', $activeBusinessId)->max('display_order');
        
        $menu = Menu::create([
            'business_id' => $activeBusinessId,
            'name' => $request->name,
            'file_path' => $path,
            'is_default' => $request->is_default ?? false,
            'category' => $request->category ?? 'General',
            'description' => $request->description,
            'display_order' => $maxOrder !== null ? $maxOrder + 1 : 0,
        ]);
        
        $menu->view_url = url('storage/' . $menu->file_path);
        
        return response()->json([
            'message' => 'Menú subido exitosamente',
            'menu' => $menu
        ], 201);
    }
    
    public function setDefaultMenu(Request $request)
    {
    $validator = Validator::make($request->all(), [
            'menu_id' => 'required|exists:menus,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        $activeBusinessId = $this->activeBusinessId($user, 'admin');
        $menu = Menu::where('id', $request->menu_id)
            ->where('business_id', $activeBusinessId)
            ->firstOrFail();
            
    Menu::where('business_id', $activeBusinessId)
            ->update(['is_default' => false]);
            
        $menu->is_default = true;
        $menu->save();
        
        return response()->json([
            'message' => 'Menú establecido como predeterminado',
            'menu' => $menu
        ]);
    }

    public function renameMenu(Request $request, Menu $menu)
    {
        $this->authorizeBusinessOwnership($menu->business_id);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $menu->name = $request->name;
        $menu->save();

        return response()->json([
            'message' => 'Menú renombrado correctamente',
            'menu' => $menu,
        ]);
    }

    public function reorderMenus(Request $request)
    {
        $request->validate([
            'menu_order' => 'required|array',
            'menu_order.*' => 'integer|exists:menus,id',
        ]);

        $user = Auth::user();
        $order = 0;
        foreach ($request->menu_order as $menuId) {
            Menu::where('id', $menuId)
                ->where('business_id', $user->business_id)
                ->update(['display_order' => $order++]);
        }

        return response()->json(['message' => 'Orden guardado correctamente']);
    }

    public function preview(Menu $menu)
    {
        $this->authorizeBusinessOwnership($menu->business_id);

        $filePath = storage_path('app/public/' . $menu->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'Archivo no encontrado');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return response()->file($filePath, [
                'Content-Type' => mime_content_type($filePath),
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        if ($extension === 'pdf') {
            if (extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->setResolution(150, 150);
                    $imagick->readImage($filePath . '[0]');
                    $imagick->setImageFormat('png');

                    $blob = $imagick->getImageBlob();

                    $imagick->clear();
                    $imagick->destroy();

                    return response($blob, 200, [
                        'Content-Type' => 'image/png',
                        'Cache-Control' => 'no-cache',
                    ]);
                } catch (\ImagickException | \Exception $e) {
                    \Log::error('Error generando preview del menú ' . $menu->id . ': ' . $e->getMessage());
                }
            }

            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        abort(415, 'Formato de archivo no soportado para preview');
    }

    public function download(Menu $menu)
    {
        $this->authorizeBusinessOwnership($menu->business_id);

        if (!Storage::disk('public')->exists($menu->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        $extension = pathinfo($menu->file_path, PATHINFO_EXTENSION);
        $fileName  = Str::slug($menu->name ?? 'menu') . '.' . $extension;

        return Storage::disk('public')->download($menu->file_path, $fileName);
    }

    private function authorizeBusinessOwnership(int $businessId): void
    {
        $user = Auth::user();
        if ($user->business_id !== $businessId) {
            abort(403, 'No tienes permiso para realizar esta acción');
        }
    }

    /**
     * Diagnóstico de límites de subida de archivos
     */
    public function uploadLimits()
    {
        // Obtener límites PHP
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $memoryLimit = ini_get('memory_limit');
        $maxExecutionTime = ini_get('max_execution_time');
        $maxInputTime = ini_get('max_input_time');
        $maxFileUploads = ini_get('max_file_uploads');

        // Convertir a bytes para comparación
        $uploadMaxBytes = $this->parseSize($uploadMaxFilesize);
        $postMaxBytes = $this->parseSize($postMaxSize);
        $memoryLimitBytes = $this->parseSize($memoryLimit);

        // Determinar el límite efectivo (el menor)
        $effectiveLimit = min($uploadMaxBytes, $postMaxBytes);
        $effectiveLimitMB = round($effectiveLimit / 1024 / 1024, 2);

        return response()->json([
            'php_limits' => [
                'upload_max_filesize' => $uploadMaxFilesize,
                'post_max_size' => $postMaxSize,
                'memory_limit' => $memoryLimit,
                'max_execution_time' => $maxExecutionTime,
                'max_input_time' => $maxInputTime,
                'max_file_uploads' => $maxFileUploads,
            ],
            'effective_limit' => [
                'bytes' => $effectiveLimit,
                'mb' => $effectiveLimitMB,
                'human' => $this->formatBytes($effectiveLimit)
            ],
            'laravel_validation_limit' => '50MB (51200 KB)',
            'recommendations' => $this->getUploadRecommendations($effectiveLimit),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]
        ]);
    }

    /**
     * Parsear tamaño de string a bytes
     */
    private function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Obtener recomendaciones basadas en límites
     */
    private function getUploadRecommendations($effectiveLimit)
    {
        $recommendations = [];
        $limitMB = $effectiveLimit / 1024 / 1024;

        if ($limitMB < 10) {
            $recommendations[] = "⚠️ Límite muy bajo ({$this->formatBytes($effectiveLimit)}). Recomendamos al menos 10MB.";
        } elseif ($limitMB < 25) {
            $recommendations[] = "🟡 Límite bajo ({$this->formatBytes($effectiveLimit)}). Considere aumentar a 50MB para menús con imágenes.";
        } else {
            $recommendations[] = "✅ Límite adecuado ({$this->formatBytes($effectiveLimit)}) para la mayoría de archivos.";
        }

        $recommendations[] = "📁 Para archivos muy grandes, considere usar formatos comprimidos o dividir el contenido.";
        $recommendations[] = "🔧 Si el problema persiste, verifique la configuración del servidor web (Apache/Nginx).";

        return $recommendations;
    }
}
