<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseRealtimeService;

class SetupFirebase extends Command
{
    protected $signature = 'firebase:setup {--test : Test Firebase connection}';
    protected $description = 'Setup and test Firebase configuration for real-time features';

    public function handle()
    {
        $this->info('🔥 Firebase Setup & Test for MOZO QR');
        $this->line('');

        // Test configuration
        $this->checkConfiguration();
        
        if ($this->option('test')) {
            $this->testConnection();
        }

        $this->provideSolutions();
    }

    private function checkConfiguration()
    {
        $this->info('📋 Checking Configuration...');
        $this->line('');

        $configs = [
            'FIREBASE_PROJECT_ID' => config('services.firebase.project_id'),
            'FIREBASE_SERVER_KEY' => config('services.firebase.server_key'),
            'FIREBASE_SERVICE_ACCOUNT_PATH' => config('services.firebase.service_account_path'),
            'FIREBASE_API_KEY' => config('services.firebase.api_key'),
            'FIREBASE_AUTH_DOMAIN' => config('services.firebase.auth_domain'),
            'FIREBASE_STORAGE_BUCKET' => config('services.firebase.storage_bucket'),
        ];

        foreach ($configs as $key => $value) {
            $status = !empty($value) ? '✅' : '❌';
            $displayValue = $key === 'FIREBASE_SERVER_KEY' || $key === 'FIREBASE_API_KEY' 
                ? (strlen($value) > 10 ? substr($value, 0, 10) . '...' : $value)
                : $value;
            
            $this->line("{$status} {$key}: " . ($displayValue ?: '(not set)'));
        }

        $this->line('');

        // Check service account file
        $serviceAccountPath = config('services.firebase.service_account_path');
        if (!empty($serviceAccountPath)) {
            if (file_exists($serviceAccountPath)) {
                $this->info("✅ Service account file exists: {$serviceAccountPath}");
                
                try {
                    $content = json_decode(file_get_contents($serviceAccountPath), true);
                    if (isset($content['project_id']) && $content['project_id'] === config('services.firebase.project_id')) {
                        $this->info("✅ Service account project ID matches");
                    } else {
                        $this->error("❌ Service account project ID mismatch");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Invalid service account JSON: " . $e->getMessage());
                }
            } else {
                $this->error("❌ Service account file not found: {$serviceAccountPath}");
            }
        } else {
            $this->error("❌ FIREBASE_SERVICE_ACCOUNT_PATH not configured");
        }

        $this->line('');
    }

    private function testConnection()
    {
        $this->info('🧪 Testing Firebase Connection...');
        $this->line('');

        try {
            $firebaseService = app(FirebaseRealtimeService::class);
            $this->info("✅ FirebaseRealtimeService instantiated");

            // Test write operation
            $testData = [
                'test' => true,
                'timestamp' => now()->toISOString(),
                'message' => 'Firebase connection test from artisan'
            ];

            // Create a test call object
            $testCall = new \stdClass();
            $testCall->id = 'test_' . uniqid();
            $testCall->table_id = 1;
            $testCall->waiter_id = 1;
            $testCall->status = 'pending';
            $testCall->message = 'Test call';
            $testCall->called_at = now();
            $testCall->acknowledged_at = null;
            $testCall->completed_at = null;

            // Mock relationships
            $testCall->table = new \stdClass();
            $testCall->table->number = 999;
            $testCall->table->business_id = 1;
            
            $testCall->waiter = new \stdClass();
            $testCall->waiter->name = 'Test Waiter';

            $result = $firebaseService->writeWaiterCall($testCall, 'test');
            
            if ($result) {
                $this->info("✅ Firebase write test successful");
            } else {
                $this->warn("⚠️  Firebase write test returned false (check logs)");
            }

        } catch (\Exception $e) {
            $this->error("❌ Firebase test failed: " . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
        }

        $this->line('');
    }

    private function provideSolutions()
    {
        $this->info('🚀 Next Steps:');
        $this->line('');

        $serviceAccountPath = config('services.firebase.service_account_path');
        if (empty($serviceAccountPath) || !file_exists($serviceAccountPath)) {
            $this->line('1. 🔑 Get Firebase Credentials:');
            $this->line('   • Go to: https://console.firebase.google.com/project/' . config('services.firebase.project_id'));
            $this->line('   • Navigate to Project Settings → Service accounts');
            $this->line('   • Click "Generate new private key"');
            $this->line('   • Save the JSON file as: ' . storage_path('app/firebase/firebase.json'));
            $this->line('');
        }

        if (empty(config('services.firebase.api_key'))) {
            $this->line('2. 🌐 Get Web App Config:');
            $this->line('   • In Firebase Console → Project Settings → General');
            $this->line('   • Scroll to "Your apps" section');
            $this->line('   • Add web app or copy existing config');
            $this->line('   • Add the config values to your .env file');
            $this->line('');
        }

        $this->line('3. 🔧 Update .env with missing values:');
        if (empty(config('services.firebase.service_account_path'))) {
            $this->line('   FIREBASE_SERVICE_ACCOUNT_PATH=' . storage_path('app/firebase/firebase.json'));
        }
        if (empty(config('services.firebase.api_key'))) {
            $this->line('   FIREBASE_API_KEY=your-web-api-key');
        }
        if (empty(config('services.firebase.messaging_sender_id'))) {
            $this->line('   FIREBASE_MESSAGING_SENDER_ID=your-sender-id');
        }
        if (empty(config('services.firebase.app_id'))) {
            $this->line('   FIREBASE_APP_ID=your-app-id');
        }

        $this->line('');
        $this->line('4. 🧪 Test again:');
        $this->line('   php artisan firebase:setup --test');
        $this->line('');

        $this->line('5. 🌐 Test in browser:');
        $this->line('   • Visit: ' . config('app.url') . '/api/firebase/status');
        $this->line('   • Visit: ' . config('app.url') . '/api/firebase/config');
        $this->line('');
    }
}