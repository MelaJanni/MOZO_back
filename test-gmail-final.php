<?php
// Test final de Gmail SMTP
echo "🔧 Testing Gmail SMTP - Final Test...\n\n";

// Cargar Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "1. Configuration loaded:\n";
echo "   MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "   MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "   MAIL_PASSWORD: " . (config('mail.mailers.smtp.password') ? 'SET ✅' : 'NOT SET ❌') . "\n";
echo "   MAIL_FROM: " . config('mail.from.address') . "\n\n";

echo "2. Testing SMTP connection...\n";
try {
    $connection = fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
    if ($connection) {
        echo "✅ SMTP connection successful\n";
        fclose($connection);
    } else {
        echo "❌ SMTP connection failed: $errstr\n";
    }
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing email send...\n";
try {
    \Illuminate\Support\Facades\Mail::raw('🎉 Gmail SMTP working! Email sent from Mozo QR system.', function($message) {
        $message->to('melajannielli@gmail.com')
               ->subject('✅ Gmail SMTP Test - Success!');
    });
    
    echo "✅ Email sent successfully!\n";
    echo "📧 Check melajannielli@gmail.com for test email\n";
    
} catch (Exception $e) {
    echo "❌ Email failed: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'Invalid login') !== false) {
        echo "\n🔧 App Password issue - try:\n";
        echo "   1. Generate new App Password in Gmail\n";
        echo "   2. Make sure 2-Step Verification is enabled\n";
        echo "   3. Use 'Mail' as app type, not 'Other'\n";
    }
}

echo "\n4. Ready for QR Email functionality! 🚀\n";
?>