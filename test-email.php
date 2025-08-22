<?php
// Archivo temporal para probar configuración de email
require_once 'vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing email configuration...\n";

try {
    // Probar conexión SMTP básica
    $connection = fsockopen('in-v3.mailjet.com', 587, $errno, $errstr, 10);
    
    if (!$connection) {
        echo "❌ Cannot connect to Mailjet SMTP: $errstr ($errno)\n";
        echo "Possible causes:\n";
        echo "- Server firewall blocking port 587\n";
        echo "- ISP blocking SMTP connections\n";
        echo "- Mailjet service down\n";
    } else {
        echo "✅ SMTP connection successful\n";
        fclose($connection);
        
        // Probar envío real
        \Illuminate\Support\Facades\Mail::raw('Test email from Laravel', function($message) {
            $message->to('test@example.com')
                   ->subject('Test Email');
        });
        
        echo "✅ Email sent successfully\n";
    }
    
} catch (Exception $e) {
    echo "❌ Email test failed: " . $e->getMessage() . "\n";
    echo "Check your .env configuration\n";
}
?>