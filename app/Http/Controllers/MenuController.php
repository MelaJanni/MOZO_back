<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'menu_file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'is_default' => 'boolean',
        ]);

        // Manejar la subida del archivo
        $path = $request->file('menu_file')->store('menus', 'public');

        // Si es default, quitar default de otros menús
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

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        return response()->json($menu);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu)
    {
        $request->validate([
            'business_id' => 'exists:businesses,id',
            'menu_file' => 'file|mimes:pdf|max:10240', // 10MB max
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
            // Borrar archivo anterior
            if (Storage::disk('public')->exists($menu->file_path)) {
                Storage::disk('public')->delete($menu->file_path);
            }
            
            // Guardar nuevo archivo
            $path = $request->file('menu_file')->store('menus', 'public');
            $data['file_path'] = $path;
        }

        $menu->update($data);

        return response()->json($menu);
    }

    /**
     * Remove the specified resource from storage.
     */
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

        // Borrar archivo
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

    /**
     * Fetch all menus for the business
     */
    public function fetchMenus()
    {
        $user = Auth::user();
        
        $menus = Menu::where('business_id', $user->business_id)
            ->orderBy('display_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Añadir URLs de visualización para cada menú
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
    
    /**
     * Upload a new menu
     */
    public function uploadMenu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'is_default' => 'sometimes|boolean',
            'category' => 'sometimes|string|max:50',
            'description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        // Guardar el archivo
        $path = $request->file('file')->store('menus/' . $user->business_id, 'public');
        
        // Si se establece como predeterminado, desmarcar los demás
        if ($request->has('is_default') && $request->is_default) {
            Menu::where('business_id', $user->business_id)
                ->update(['is_default' => false]);
        }
        
        // Crear el menú
        $maxOrder = Menu::where('business_id', $user->business_id)->max('display_order');
        
        $menu = Menu::create([
            'business_id' => $user->business_id,
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
    
    /**
     * Set a menu as default
     */
    public function setDefaultMenu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_id' => 'required|exists:menus,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        // Verificar que el menú pertenece al negocio
        $menu = Menu::where('id', $request->menu_id)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
            
        // Desmarcar todos los menús como predeterminados
        Menu::where('business_id', $user->business_id)
            ->update(['is_default' => false]);
            
        // Marcar el seleccionado como predeterminado
        $menu->is_default = true;
        $menu->save();
        
        return response()->json([
            'message' => 'Menú establecido como predeterminado',
            'menu' => $menu
        ]);
    }

    /**
     * Renombrar un menú existente
     */
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

    /**
     * Reordenar menús mediante drag-and-drop
     * Espera un array "menu_order" en el cuerpo con los IDs en el orden deseado.
     */
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

    /**
     * Generar una miniatura de la primera página del menú (PDF/JPG/PNG)
     * Por simplicidad, este método devuelve directamente la URL pública si ya es imagen.
     * Para PDF se requiere la extensión Imagick habilitada en el servidor.
     */
    public function preview(Menu $menu)
    {
        $this->authorizeBusinessOwnership($menu->business_id);

        $filePath = storage_path('app/public/' . $menu->file_path);

        // Verificar que el archivo exista
        if (!file_exists($filePath)) {
            abort(404, 'Archivo no encontrado');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Si es imagen, servirla directamente con el tipo MIME correcto
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return response()->file($filePath, [
                'Content-Type' => mime_content_type($filePath),
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        // Si es PDF intentamos generar miniatura PNG con Imagick
        if ($extension === 'pdf') {
            if (extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->setResolution(150, 150);
                    $imagick->readImage($filePath . '[0]');
                    $imagick->setImageFormat('png');

                    $blob = $imagick->getImageBlob();

                    // Liberar recursos
                    $imagick->clear();
                    $imagick->destroy();

                    return response($blob, 200, [
                        'Content-Type' => 'image/png',
                        'Cache-Control' => 'no-cache',
                    ]);
                } catch (\ImagickException | \Exception $e) {
                    \Log::error('Error generando preview del menú ' . $menu->id . ': ' . $e->getMessage());
                    // Si falla, devolvemos el PDF original para que al menos se visualice
                }
            }

            // En caso de no disponer de Imagick o si ocurrió un error, devolvemos el PDF para vista directa en el navegador
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Para otros formatos no soportados
        abort(415, 'Formato de archivo no soportado para preview');
    }

    /**
     * Descargar el archivo original del menú
     * Devuelve la respuesta con cabecera Content-Disposition: attachment para forzar la descarga.
     */
    public function download(Menu $menu)
    {
        $this->authorizeBusinessOwnership($menu->business_id);

        if (!Storage::disk('public')->exists($menu->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        // Nombre sugerido para el archivo
        $extension = pathinfo($menu->file_path, PATHINFO_EXTENSION);
        $fileName  = Str::slug($menu->name ?? 'menu') . '.' . $extension;

        return Storage::disk('public')->download($menu->file_path, $fileName);
    }

    /**
     * Helper: verifica que el usuario autenticado sea dueño del negocio indicado
     */
    private function authorizeBusinessOwnership(int $businessId): void
    {
        $user = Auth::user();
        if ($user->business_id !== $businessId) {
            abort(403, 'No tienes permiso para realizar esta acción');
        }
    }
}
