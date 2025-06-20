<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'Códigos QR' }}</title>
    <style>
        body { font-family: Arial, sans-serif; color:#333;background:#f5f5f5;margin:0;padding:0; }
        .container{ max-width:600px;margin:0 auto;background:#ffffff;padding:20px;border-radius:8px; }
        h1{ color:#2b2b2b; font-size:22px; margin-top:0; }
        p{ line-height:1.6; }
        .qr-wrapper{ text-align:center; margin:25px 0; }
        .footer{ font-size:12px;color:#777;text-align:center;margin-top:30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $businessName }} - Códigos QR</h1>
        <p>{{ $introText }}</p>

        @foreach($qrCodes as $qr)
            <div class="qr-wrapper">
                <h3>Mesa {{ $qr['table_number'] }}</h3>
                <img src="data:image/png;base64,{{ $qr['base64'] }}" alt="QR Mesa {{ $qr['table_number'] }}" style="width:220px;max-width:90%;" />
            </div>
        @endforeach

        <p class="footer">Enviado con Mozo App · {{ date('Y') }}</p>
    </div>
</body>
</html> 