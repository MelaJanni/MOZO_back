<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Models\Table;
use App\Models\WorkExperience;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        
        if ($userId) {
            $profiles = Profile::where('user_id', $userId)->get();
        } else {
            $profiles = Profile::all();
        }
        
        return response()->json($profiles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'table_ids' => 'array',
            'table_ids.*' => 'exists:tables,id',
        ]);

        $profile = Profile::create([
            'user_id' => $request->user_id,
            'name' => $request->name,
        ]);

        if ($request->has('table_ids')) {
            $profile->tables()->attach($request->table_ids);
        }

        return response()->json($profile->load('tables'), 201);
    }

    public function show(Profile $profile)
    {
        return response()->json($profile->load('tables'));
    }

    public function update(Request $request, Profile $profile)
    {
        $request->validate([
            'user_id' => 'exists:users,id',
            'name' => 'string|max:255',
            'table_ids' => 'array',
            'table_ids.*' => 'exists:tables,id',
        ]);

        $profile->update($request->only(['user_id', 'name']));

        if ($request->has('table_ids')) {
            $profile->tables()->sync($request->table_ids);
        }

        return response()->json($profile->load('tables'));
    }

    public function destroy(Profile $profile)
    {
        $profile->tables()->detach();
        $profile->delete();
        return response()->json(null, 204);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile()->firstOrCreate([]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8|confirmed',
            
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string',
            'profile_picture' => 'sometimes|string',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|string',
            'height' => 'sometimes|numeric',
            'weight' => 'sometimes|numeric',
            'skills' => 'sometimes|array',
            'latitude' => ['sometimes', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'longitude' => ['sometimes', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->fill($request->only(['name', 'email']));
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $profile->fill($request->except(['name', 'email', 'password']));
        $profile->save();

        return response()->json([
            'message' => 'Perfil actualizado exitosamente.',
            'user' => $user->load('profile'),
        ]);
    }
    
    public function sendWhatsAppMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        
        if (!preg_match('/^\d{10,15}$/', $phone)) {
            return response()->json([
                'message' => 'El número de teléfono no tiene un formato válido para WhatsApp'
            ], 422);
        }
        
        
        \Log::info("Usuario {$user->id} ({$user->name}) envió un mensaje WhatsApp a {$phone}");
        
        return response()->json([
            'message' => 'Mensaje WhatsApp enviado exitosamente',
            'to' => $phone,
            'status' => 'sent'
        ]);
    }

    public function getWorkHistory()
    {
        $workHistory = Auth::user()->workExperiences;
        return response()->json($workHistory);
    }

    public function addWorkHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workExperience = Auth::user()->workExperiences()->create($request->all());

        return response()->json($workExperience, 201);
    }

    public function updateWorkHistory(Request $request, WorkExperience $workExperience)
    {
        if ($workExperience->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'company' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workExperience->update($request->all());

        return response()->json($workExperience);
    }

    public function deleteWorkHistory(WorkExperience $workExperience)
    {
        if ($workExperience->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $workExperience->delete();

        return response()->json(null, 204);
    }

    public function storeDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'sometimes|string|in:android,ios,web',
        ]);

        $request->user()->deviceTokens()->updateOrCreate(
            ['token' => $request->token],
            ['platform' => $request->platform]
        );

        return response()->json(['message' => 'Token guardado'], 201);
    }

    public function deleteDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $deleted = $request->user()->deviceTokens()->where('token', $request->token)->delete();

        return response()->json(['message' => $deleted ? 'Token eliminado' : 'Token no encontrado']);
    }
}
