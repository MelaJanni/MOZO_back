<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FirebaseHttpClient Trait
 * 
 * Provides HTTP methods for interacting with Firebase Realtime Database.
 * This trait eliminates code duplication across Firebase services.
 * 
 * @package App\Services\Concerns
 */
trait FirebaseHttpClient
{
    /**
     * Base URL for Firebase Realtime Database
     */
    protected function getFirebaseBaseUrl(): string
    {
        return config('services.firebase.database_url', 'https://mozoqr-7d32c-default-rtdb.firebaseio.com');
    }

    /**
     * Write data to Firebase RTDB at specified path
     * 
     * @param string $path Path in Firebase (e.g., '/calls/123')
     * @param array $data Data to write
     * @return bool Success status
     */
    protected function writeToFirebase(string $path, array $data): bool
    {
        try {
            $url = $this->getFirebaseBaseUrl() . $path . '.json';
            
            $response = Http::put($url, $data);
            
            if ($response->successful()) {
                Log::debug('Firebase write successful', [
                    'path' => $path,
                    'data_keys' => array_keys($data)
                ]);
                return true;
            }
            
            Log::warning('Firebase write failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Firebase write exception', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Read data from Firebase RTDB at specified path
     * 
     * @param string $path Path in Firebase
     * @return array|null Data or null if not found
     */
    protected function readFromFirebase(string $path): ?array
    {
        try {
            $url = $this->getFirebaseBaseUrl() . $path . '.json';
            
            $response = Http::get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                return is_array($data) ? $data : null;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Firebase read exception', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete data from Firebase RTDB at specified path
     * 
     * @param string $path Path in Firebase
     * @return bool Success status
     */
    protected function deleteFromFirebase(string $path): bool
    {
        try {
            $url = $this->getFirebaseBaseUrl() . $path . '.json';
            
            $response = Http::delete($url);
            
            if ($response->successful()) {
                Log::debug('Firebase delete successful', ['path' => $path]);
                return true;
            }
            
            Log::warning('Firebase delete failed', [
                'path' => $path,
                'status' => $response->status()
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Firebase delete exception', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Patch (partial update) data in Firebase RTDB
     * 
     * @param string $path Path in Firebase
     * @param array $data Data to patch
     * @return bool Success status
     */
    protected function patchFirebase(string $path, array $data): bool
    {
        try {
            $url = $this->getFirebaseBaseUrl() . $path . '.json';
            
            $response = Http::patch($url, $data);
            
            if ($response->successful()) {
                Log::debug('Firebase patch successful', ['path' => $path]);
                return true;
            }
            
            Log::warning('Firebase patch failed', [
                'path' => $path,
                'status' => $response->status()
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Firebase patch exception', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
