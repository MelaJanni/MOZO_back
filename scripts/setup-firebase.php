<?php
/**
 * Script para configurar Firebase automáticamente
 * Genera las credenciales necesarias y configura el proyecto
 */

echo "🔥 CONFIGURANDO FIREBASE PARA MOZOQR\n\n";

$projectId = 'mozoqr-7d32c';
$firebaseDir = __DIR__ . '/../storage/app/firebase';

// Crear directorio si no existe
if (!is_dir($firebaseDir)) {
    mkdir($firebaseDir, 0755, true);
    echo "✅ Directorio Firebase creado: $firebaseDir\n";
}

// Verificar si ya existe configuración
$configFile = $firebaseDir . '/firebase.json';
if (file_exists($configFile)) {
    echo "⚠️  Archivo de configuración ya existe en: $configFile\n";
    $existingConfig = json_decode(file_get_contents($configFile), true);
    if (isset($existingConfig['project_id']) && $existingConfig['project_id'] === $projectId) {
        echo "✅ Proyecto ID coincide: {$existingConfig['project_id']}\n";
    }
}

// Instrucciones para obtener credenciales reales
echo "\n📋 PASOS PARA COMPLETAR LA CONFIGURACIÓN:\n\n";
echo "1. Ve a Firebase Console: https://console.firebase.google.com/project/$projectId\n";
echo "2. Ve a 'Project Settings' (⚙️ en la sidebar)\n";
echo "3. Ve a la pestaña 'Service accounts'\n";
echo "4. Click en 'Generate new private key'\n";
echo "5. Descarga el archivo JSON\n";
echo "6. Reemplaza el contenido de: $configFile\n\n";

// Generar template con la estructura correcta
$template = [
    'type' => 'service_account',
    'project_id' => $projectId,
    'private_key_id' => 'REPLACE_WITH_ACTUAL_PRIVATE_KEY_ID',
    'private_key' => 'REPLACE_WITH_ACTUAL_PRIVATE_KEY',
    'client_email' => "REPLACE_WITH_ACTUAL_CLIENT_EMAIL@$projectId.iam.gserviceaccount.com",
    'client_id' => 'REPLACE_WITH_ACTUAL_CLIENT_ID',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    'client_x509_cert_url' => "https://www.googleapis.com/robot/v1/metadata/x509/REPLACE_WITH_ACTUAL_CLIENT_EMAIL%40$projectId.iam.gserviceaccount.com",
    'universe_domain' => 'googleapis.com'
];

// Escribir template si no existe archivo válido
if (!file_exists($configFile) || json_decode(file_get_contents($configFile), true) === null) {
    file_put_contents($configFile, json_encode($template, JSON_PRETTY_PRINT));
    echo "✅ Template creado en: $configFile\n";
    echo "   ⚠️  REEMPLAZA LOS VALORES 'REPLACE_WITH_ACTUAL_*' con los datos reales\n\n";
}

// Verificar configuración .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    
    echo "🔍 VERIFICANDO CONFIGURACIÓN .ENV:\n\n";
    
    $checks = [
        'FIREBASE_PROJECT_ID=' . $projectId => strpos($envContent, 'FIREBASE_PROJECT_ID=' . $projectId) !== false,
        'FIREBASE_SERVER_KEY=' => strpos($envContent, 'FIREBASE_SERVER_KEY=AIza') !== false,
        'FIREBASE_SERVICE_ACCOUNT_PATH=' => strpos($envContent, 'FIREBASE_SERVICE_ACCOUNT_PATH=') !== false,
    ];
    
    foreach ($checks as $config => $exists) {
        echo ($exists ? '✅' : '❌') . " $config\n";
    }
    
    // Agregar configuraciones faltantes para frontend
    $missingConfigs = [];
    if (strpos($envContent, 'FIREBASE_API_KEY=') === false) {
        $missingConfigs[] = 'FIREBASE_API_KEY=your-web-api-key-here';
    }
    if (strpos($envContent, 'FIREBASE_AUTH_DOMAIN=') === false) {
        $missingConfigs[] = "FIREBASE_AUTH_DOMAIN=$projectId.firebaseapp.com";
    }
    if (strpos($envContent, 'FIREBASE_STORAGE_BUCKET=') === false) {
        $missingConfigs[] = "FIREBASE_STORAGE_BUCKET=$projectId.appspot.com";
    }
    if (strpos($envContent, 'FIREBASE_MESSAGING_SENDER_ID=') === false) {
        $missingConfigs[] = 'FIREBASE_MESSAGING_SENDER_ID=your-sender-id-here';
    }
    if (strpos($envContent, 'FIREBASE_APP_ID=') === false) {
        $missingConfigs[] = 'FIREBASE_APP_ID=your-app-id-here';
    }
    
    if (!empty($missingConfigs)) {
        echo "\n📝 AGREGAR AL .ENV:\n\n";
        foreach ($missingConfigs as $config) {
            echo "$config\n";
        }
    }
}

// Generar reglas de Firestore
$rulesFile = $firebaseDir . '/firestore.rules';
$rules = '
rules_version = "2";
service cloud.firestore {
  match /databases/{database}/documents {
    // Reglas para llamadas de mozo - permitir lectura/escritura autenticada
    match /waiters/{waiterId}/calls/{callId} {
      allow read, write: if request.auth != null;
    }
    
    match /tables/{tableId}/waiter_calls/{callId} {
      allow read, write: if request.auth != null;
    }
    
    match /businesses/{businessId}/waiter_calls/{callId} {
      allow read, write: if request.auth != null;
    }
    
    // Reglas para estado de mesas
    match /tables/{tableId}/status/{document} {
      allow read, write: if request.auth != null;
    }
    
    match /businesses/{businessId}/table_status/{tableId} {
      allow read, write: if request.auth != null;
    }
    
    // Notificaciones de usuario
    match /users/{userId}/notifications/{notificationId} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
    
    // Permitir lectura pública para debugging (remover en producción)
    match /{document=**} {
      allow read: if resource.data.public == true;
    }
  }
}
';

file_put_contents($rulesFile, trim($rules));
echo "\n✅ Reglas de Firestore generadas en: $rulesFile\n";

echo "\n🚀 PRÓXIMOS PASOS:\n\n";
echo "1. Configura las credenciales reales en $configFile\n";
echo "2. Agrega las configuraciones faltantes al .env\n";
echo "3. Despliega las reglas de Firestore:\n";
echo "   firebase deploy --only firestore:rules --project $projectId\n";
echo "4. Prueba la conexión con: php artisan tinker\n";
echo "5. Ejecuta: app(App\\Services\\FirebaseRealtimeService::class)\n\n";

echo "🎉 Setup completado!\n";
?>