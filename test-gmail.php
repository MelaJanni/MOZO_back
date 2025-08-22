<?php
// Test Gmail SMTP configuration
echo "🔧 Testing Gmail SMTP Configuration...\n\n";

// Test 1: Check SMTP connectivity
echo "1. Testing SMTP connection to Gmail...\n";
$connection = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);

if (!$connection) {
    echo "❌ Cannot connect to Gmail SMTP: $errstr ($errno)\n";
    echo "   Check your server's firewall settings\n\n";
} else {
    echo "✅ SMTP connection to Gmail successful\n\n";
    fclose($connection);
}

// Test 2: Check .env configuration
echo "2. Checking .env configuration...\n";
$envFile = file_get_contents('.env');

if (strpos($envFile, 'smtp.gmail.com') !== false) {
    echo "✅ Gmail SMTP host configured\n";
} else {
    echo "❌ Gmail SMTP host not found in .env\n";
}

if (strpos($envFile, 'MAIL_USERNAME=melajannielli@gmail.com') !== false) {
    echo "✅ Email username configured\n";
} else {
    echo "❌ Email username not configured\n";
}

if (strpos($envFile, 'PENDIENTE_APP_PASSWORD') !== false) {
    echo "⚠️  App password not yet configured\n";
    echo "   You need to set your Gmail App Password\n";
} else {
    echo "✅ App password appears to be set\n";
}

echo "\n3. Next steps:\n";
echo "📧 You need to generate a Gmail App Password:\n";
echo "   1. Go to: https://myaccount.google.com/security\n";
echo "   2. Enable 2-Step Verification (if not enabled)\n";
echo "   3. Generate App Password for 'Mail'\n";
echo "   4. Replace PENDIENTE_APP_PASSWORD in .env with the 16-character password\n";
echo "   5. Run: php test-gmail.php again\n\n";

echo "🔧 To update the password, run:\n";
echo "   nano .env\n";
echo "   # Change MAIL_PASSWORD=PENDIENTE_APP_PASSWORD to your real app password\n\n";
?>