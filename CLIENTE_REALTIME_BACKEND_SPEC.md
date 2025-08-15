# 🔥 Backend Integration for Client Real-time Notifications

## 📋 **Current Endpoints Analysis**

### ✅ **Existing Endpoints (Working)**
```php
POST /api/waiter/calls/{callId}/acknowledge
POST /api/waiter/calls/{callId}/complete
```

### ❌ **Missing Functionality**
- No Firebase write to notify client in real-time
- No push notifications for offline clients

---

## 🚀 **Required Backend Changes**

### **1. Firebase Realtime Database Structure for Clients**

```javascript
// Client Frontend listens to:
/tables/{tableId}/call_status/{callId}

// Structure:
{
  "tables": {
    "5": {
      "call_status": {
        "call_xyz123": {
          "status": "acknowledged",        // pending → acknowledged → completed
          "waiter_id": 2,
          "waiter_name": "Juan Pérez",
          "acknowledged_at": 1692123456789,
          "completed_at": null,
          "message": "Tu mozo recibió la solicitud"
        }
      }
    }
  }
}
```

### **2. Backend Endpoints Modification**

#### **Acknowledge Endpoint Enhancement:**
```php
POST /api/waiter/calls/{callId}/acknowledge

// Current: Updates database only
// Required: 
// 1. Update database
// 2. Write to Firebase Realtime Database
// 3. Send push notification if client offline

Response:
{
  "success": true,
  "message": "Call acknowledged",
  "call": {
    "id": "xyz123",
    "status": "acknowledged",
    "acknowledged_at": "2023-08-15T10:30:00Z"
  }
}
```

#### **Complete Endpoint Enhancement:**
```php
POST /api/waiter/calls/{callId}/complete

// Current: Updates database only  
// Required:
// 1. Update database
// 2. Write to Firebase Realtime Database
// 3. Send push notification if client offline
// 4. Remove from Firebase after 30 seconds

Response:
{
  "success": true,
  "message": "Call completed",
  "call": {
    "id": "xyz123", 
    "status": "completed",
    "completed_at": "2023-08-15T10:35:00Z"
  }
}
```

---

## 🔧 **Firebase Integration Code for Backend**

### **Firebase Config (Laravel example):**
```php
// config/firebase.php
return [
    'database_url' => 'https://mozoqr-7d32c-default-rtdb.firebaseio.com',
    'credentials' => storage_path('app/firebase-credentials.json')
];
```

### **Firebase Service for Backend:**
```php
<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;

class FirebaseRealtimeService 
{
    private $database;
    
    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->withDatabaseUri(config('firebase.database_url'));
            
        $this->database = $factory->createDatabase();
    }
    
    public function notifyClientCallAcknowledged($tableId, $callId, $waiterData)
    {
        $ref = $this->database->getReference("tables/{$tableId}/call_status/{$callId}");
        
        $ref->set([
            'status' => 'acknowledged',
            'waiter_id' => $waiterData['id'],
            'waiter_name' => $waiterData['name'],
            'acknowledged_at' => now()->timestamp * 1000, // JavaScript timestamp
            'message' => 'Tu mozo recibió la solicitud'
        ]);
    }
    
    public function notifyClientCallCompleted($tableId, $callId, $waiterData)
    {
        $ref = $this->database->getReference("tables/{$tableId}/call_status/{$callId}");
        
        $ref->update([
            'status' => 'completed',
            'completed_at' => now()->timestamp * 1000,
            'message' => 'Servicio completado'
        ]);
        
        // Auto-remove after 30 seconds
        $this->scheduleRemoval($tableId, $callId, 30);
    }
    
    private function scheduleRemoval($tableId, $callId, $seconds)
    {
        // Use Laravel queue job or similar
        dispatch(function() use ($tableId, $callId) {
            $ref = $this->database->getReference("tables/{$tableId}/call_status/{$callId}");
            $ref->remove();
        })->delay(now()->addSeconds($seconds));
    }
}
```

### **Enhanced Controller Methods:**
```php
<?php

namespace App\Http\Controllers\Waiter;

use App\Services\FirebaseRealtimeService;
use App\Services\PushNotificationService;

class WaiterCallController extends Controller
{
    private $firebaseService;
    private $pushService;
    
    public function __construct(
        FirebaseRealtimeService $firebaseService,
        PushNotificationService $pushService
    ) {
        $this->firebaseService = $firebaseService;
        $this->pushService = $pushService;
    }
    
    public function acknowledge($callId)
    {
        $call = Call::findOrFail($callId);
        $waiter = auth()->user();
        
        // 1. Update database
        $call->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'waiter_id' => $waiter->id
        ]);
        
        // 2. Notify client via Firebase Realtime Database
        $this->firebaseService->notifyClientCallAcknowledged(
            $call->table_id,
            $callId,
            [
                'id' => $waiter->id,
                'name' => $waiter->name
            ]
        );
        
        // 3. Send push notification to client
        $this->pushService->sendToTable($call->table_id, [
            'title' => 'Tu mozo está en camino',
            'body' => "Tu mozo {$waiter->name} recibió la solicitud",
            'data' => [
                'type' => 'call_acknowledged',
                'call_id' => $callId,
                'waiter_name' => $waiter->name
            ]
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Call acknowledged',
            'call' => $call->fresh()
        ]);
    }
    
    public function complete($callId)
    {
        $call = Call::findOrFail($callId);
        $waiter = auth()->user();
        
        // 1. Update database
        $call->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
        
        // 2. Notify client via Firebase Realtime Database
        $this->firebaseService->notifyClientCallCompleted(
            $call->table_id,
            $callId,
            [
                'id' => $waiter->id,
                'name' => $waiter->name
            ]
        );
        
        // 3. Send push notification to client
        $this->pushService->sendToTable($call->table_id, [
            'title' => 'Servicio completado',
            'body' => 'Tu solicitud ha sido atendida',
            'data' => [
                'type' => 'call_completed',
                'call_id' => $callId
            ]
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Call completed',
            'call' => $call->fresh()
        ]);
    }
}
```

---

## 📱 **Push Notification Service**

```php
<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationService
{
    private $messaging;
    
    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials'));
            
        $this->messaging = $factory->createMessaging();
    }
    
    public function sendToTable($tableId, $payload)
    {
        // Get device tokens for clients at this table
        $tokens = $this->getTableDeviceTokens($tableId);
        
        if (empty($tokens)) {
            return false; // No clients to notify
        }
        
        $message = CloudMessage::new()
            ->withNotification(Notification::create(
                $payload['title'],
                $payload['body']
            ))
            ->withData($payload['data'] ?? []);
            
        // Send to multiple tokens
        $this->messaging->sendMulticast($message, $tokens);
        
        return true;
    }
    
    private function getTableDeviceTokens($tableId)
    {
        // Query your database for device tokens
        // associated with clients currently at this table
        return ClientDeviceToken::where('table_id', $tableId)
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->toArray();
    }
}
```

---

## 🎯 **Frontend Client Integration**

### **Client listens to Firebase:**
```javascript
// In client app (customer interface)
import { ref, onValue } from 'firebase/database';

function listenForWaiterUpdates(tableId, callId) {
    const statusRef = ref(database, `tables/${tableId}/call_status/${callId}`);
    
    onValue(statusRef, (snapshot) => {
        const status = snapshot.val();
        
        if (status) {
            switch(status.status) {
                case 'acknowledged':
                    showNotification('Tu mozo está en camino', status.message);
                    playSound();
                    break;
                    
                case 'completed':
                    showNotification('Servicio completado', status.message);
                    removeCallFromUI();
                    break;
            }
        }
    });
}
```

---

## 📦 **Required Dependencies**

### **Composer (Laravel):**
```bash
composer require kreait/firebase-php
```

### **NPM (Frontend):**
```bash
npm install firebase
```

---

## ✅ **Implementation Checklist**

- [ ] Install Firebase PHP SDK in backend
- [ ] Configure Firebase credentials and database URL
- [ ] Create FirebaseRealtimeService class
- [ ] Update acknowledge() controller method
- [ ] Update complete() controller method  
- [ ] Create PushNotificationService class
- [ ] Test Firebase writes from backend
- [ ] Test client real-time listening
- [ ] Test push notifications for offline clients
- [ ] Implement auto-cleanup of completed calls

This specification provides complete backend integration for real-time client notifications when waiters acknowledge or complete calls.