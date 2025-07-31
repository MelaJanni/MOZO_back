<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Profile;
use App\Models\Table;
use App\Models\User;
use App\Notifications\TableCalledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WaiterController extends Controller
{
    public function onboardBusiness(Request $request)
    {
        $request->validate([
            'business_id' => 'sometimes|exists:businesses,id',
            'join_code' => 'sometimes|string|exists:businesses,join_code',
            'code' => 'sometimes|string|exists:businesses,join_code',
        ]);

        $user = $request->user();

        if (!$user->isWaiter()) {
            return response()->json([
                'message' => 'Solo los usuarios con rol de camarero pueden unirse a un negocio',
            ], 403);
        }

        if ($request->filled('business_id')) {
            $business = Business::findOrFail($request->business_id);
        } else {
            $joinCode = $request->join_code ?? $request->code;
            $business = Business::where('join_code', $joinCode)->first();
            if (!$business) {
                return response()->json([
                    'message' => 'Código de negocio no válido',
                ], 404);
            }
        }

        $user->active_business_id = $business->id;
        $user->save();

        return response()->json([
            'message' => 'Te has unido al negocio correctamente',
            'business' => $business,
        ]);
    }

    public function listTables(Request $request)
    {
        $user = $request->user();

        if (!$user->business_id) {
            return response()->json([
                'message' => 'No estás vinculado a ningún negocio',
                'tables' => [],
            ]);
        }

        $tables = Table::where('business_id', $user->business_id)->get();

        return response()->json([
            'tables' => $tables,
        ]);
    }

    public function toggleTableNotifications($tableId)
    {
        $user = Auth::user();
        
        $table = Table::where('id', $tableId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
            
        $table->notifications_enabled = !$table->notifications_enabled;
        $table->save();
        
        return response()->json([
            'message' => 'Estado de notificaciones actualizado',
            'table' => $table,
            'notifications_enabled' => $table->notifications_enabled
        ]);
    }

    public function globalNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        Table::where('business_id', $user->business_id)
            ->update(['notifications_enabled' => $request->enabled]);
            
        return response()->json([
            'message' => $request->enabled 
                ? 'Notificaciones habilitadas para todas las mesas' 
                : 'Notificaciones deshabilitadas para todas las mesas',
            'notifications_enabled' => $request->enabled
        ]);
    }

    public function listNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(10);

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    public function respondNotification(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|string',
            'action' => 'required|in:accept,deny,spam',
        ]);

        $user = $request->user();
        $notification = $user->notifications()->where('id', $request->notification_id)->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notificación no encontrada',
            ], 404);
        }

        $notification->markAsRead();

        if ($request->action === 'spam') {
        }

        return response()->json([
            'message' => 'Notificación procesada exitosamente',
        ]);
    }

    public function listProfiles(Request $request)
    {
        $user = $request->user();
        
        $profiles = Profile::where('user_id', $user->id)
            ->with('tables')
            ->get();
        
        return response()->json([
            'profiles' => $profiles,
        ]);
    }

    public function createProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tables' => 'sometimes|array',
            'tables.*' => 'exists:tables,id',
        ]);
        
        $user = $request->user();
        
        $profile = Profile::create([
            'user_id' => $user->id,
            'name' => $request->name,
        ]);
        
        if ($request->has('tables')) {
            $businessTables = Table::where('business_id', $user->business_id)
                ->whereIn('id', $request->tables)
                ->pluck('id');
            
            $profile->tables()->attach($businessTables);
        }
        
        return response()->json([
            'message' => 'Perfil creado exitosamente',
            'profile' => $profile->load('tables'),
        ], 201);
    }

    public function deleteProfile($id, Request $request)
    {
        $user = $request->user();
        
        $profile = Profile::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $profile->tables()->detach();
        
        $profile->delete();
        
        return response()->json([
            'message' => 'Perfil eliminado exitosamente',
        ]);
    }
    
    public function fetchWaiterTables()
    {
        $user = Auth::user();
        
        $tables = Table::where('business_id', $user->business_id)
            ->orderBy('number', 'asc')
            ->get();
            
        $profileTables = DB::table('profile_table')
            ->join('profiles', 'profiles.id', '=', 'profile_table.profile_id')
            ->where('profiles.user_id', $user->id)
            ->pluck('table_id')
            ->toArray();
            
        $tables->transform(function ($table) use ($profileTables) {
            $table->is_assigned = in_array($table->id, $profileTables);
            return $table;
        });
        
        return response()->json([
            'tables' => $tables,
            'assigned_count' => count($profileTables)
        ]);
    }
    
    public function fetchWaiterNotifications()
    {
        $user = Auth::user();
        
        $unreadNotifications = $user->unreadNotifications;
        
        $readNotifications = $user->readNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
            
        return response()->json([
            'unread_notifications' => $unreadNotifications,
            'read_notifications' => $readNotifications,
            'unread_count' => $unreadNotifications->count()
        ]);
    }
    
    public function handleNotification(Request $request, $notificationId)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:mark_as_read,delete',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        $notification = $user->notifications()->where('id', $notificationId)->first();
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notificación no encontrada'
            ], 404);
        }
        
        if ($request->action === 'mark_as_read') {
            $notification->markAsRead();
            
            return response()->json([
                'message' => 'Notificación marcada como leída',
                'notification' => $notification
            ]);
        } else if ($request->action === 'delete') {
            $notification->delete();
            
            return response()->json([
                'message' => 'Notificación eliminada'
            ]);
        }
    }
} 