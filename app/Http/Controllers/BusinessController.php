<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class BusinessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $businesses = Business::all();
        return response()->json($businesses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $business = Business::create([
            'name' => $request->name,
            'code' => Str::random(8),
        ]);

        return response()->json($business, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Business $business)
    {
        return response()->json($business);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Business $business)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $business->update([
            'name' => $request->name,
        ]);

        return response()->json($business);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Business $business)
    {
        $business->delete();
        return response()->json(null, 204);
    }

    /**
     * Allows a user to join a business using a join code.
     */
    public function join(Request $request)
    {
        $request->validate([
            'join_code' => 'required|string|exists:businesses,join_code',
        ]);

        $user = Auth::user();
        $business = Business::where('join_code', $request->join_code)->firstOrFail();

        // Check if the user is already associated with the business
        if ($user->businesses()->where('business_id', $business->id)->exists()) {
            return response()->json(['message' => 'Ya eres miembro de este negocio.'], 409);
        }

        // Attach the user to the business
        $user->businesses()->attach($business->id);

        // Set the business as active if the user has no active business
        if (is_null($user->active_business_id)) {
            $user->active_business_id = $business->id;
            $user->save();
        }

        return response()->json([
            'message' => 'Te has unido al negocio exitosamente.',
            'business' => $business,
        ], 200);
    }

    /**
     * Switches the user's active business.
     */
    public function switchActive(Request $request, $businessId)
    {
        $user = Auth::user();

        // Check if the user is part of the business they want to switch to
        if (!$user->businesses()->where('business_id', $businessId)->exists()) {
            return response()->json(['message' => 'No tienes permiso para acceder a este negocio.'], 403);
        }

        $user->active_business_id = $businessId;
        $user->save();
        
        $business = Business::find($businessId);

        return response()->json([
            'message' => 'Negocio activo cambiado exitosamente.',
            'active_business' => $business,
        ], 200);
    }
}
