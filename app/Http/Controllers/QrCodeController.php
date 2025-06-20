<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use ZipArchive;
use Hashids\Hashids;

class QrCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tableId = $request->query('table_id');
        
        if ($tableId) {
            $qrCodes = QrCode::where('table_id', $tableId)->get();
        } else {
            $qrCodes = QrCode::all();
        }
        
        return response()->json($qrCodes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
        ]);

        // Generar un código único para el QR
        $codeData = $this->generateUniqueCode();

        $qrCode = QrCode::create([
            'table_id' => $request->table_id,
            'code_data' => $codeData,
        ]);

        return response()->json($qrCode, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(QrCode $qrCode)
    {
        return response()->json($qrCode);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QrCode $qrCode)
    {
        $request->validate([
            'table_id' => 'exists:tables,id',
        ]);

        if ($request->has('regenerate') && $request->regenerate) {
            $qrCode->code_data = $this->generateUniqueCode();
        }

        if ($request->has('table_id')) {
            $qrCode->table_id = $request->table_id;
        }

        $qrCode->save();

        return response()->json($qrCode);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QrCode $qrCode)
    {
        $qrCode->delete();
        return response()->json(null, 204);
    }

    /**
     * Generar un código único para el QR
     */
    private function generateUniqueCode()
    {
        $unique = false;
        $codeData = '';

        while (!$unique) {
            $codeData = Str::random(16);
            $exists = QrCode::where('code_data', $codeData)->exists();
            $unique = !$exists;
        }

        return $codeData;
    }

    /**
     * Genera y guarda un código QR para una mesa específica.
     * Este método estático puede ser reutilizado por otros controladores.
     *
     * @param Table $table La instancia de la mesa.
     * @return QrCode La instancia del código QR recién creado.
     */
    public static function generateForTable(Table $table): QrCode
    {
        $business = $table->business;

        // Instancia de Hashids usando APP_KEY como salt y longitud mínima 6
        $hashids = new Hashids(config('app.key'), 6);
        $hash    = $hashids->encode($table->id);

        // Slug del negocio
        $slug = $business->slug;

        // Base configurable del frontend
        $baseUrl = config('app.frontend_url', 'https://mozo.com.ar');

        // URL final para el QR
        $qrUrl = rtrim($baseUrl, '/') . "/QR/{$slug}/{$hash}";

        // Guardar/actualizar registro QR
        return QrCode::updateOrCreate(
            ['table_id' => $table->id],
            [
                'business_id' => $business->id,
                'code'        => $hash,
                'url'         => $qrUrl,
            ]
        );
    }

    /**
     * Generate QR code for a table (Endpoint-specific method)
     */
    public function generateQRCode($tableId)
    {
        $user = Auth::user();
        
        $table = Table::where('id', $tableId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();
            
        $qrCode = self::generateForTable($table);
        
        return response()->json([
            'message' => 'Código QR generado/actualizado exitosamente',
            'qr_code' => $qrCode,
        ]);
    }

    /**
     * Devuelve la imagen del QR de una mesa para previsualización.
     */
    public function preview($tableId)
    {
        $user = Auth::user();
        $table = Table::where('id', $tableId)
            ->where('business_id', $user->business_id)
            ->firstOrFail();

        $qrCode = $table->qrCode; // Usa la relación que definimos

        if (!$qrCode) {
            return response()->json(['message' => 'Esta mesa aún no tiene un QR generado.'], 404);
        }

        // Generamos el QR en formato SVG, que es ligero y escalable
        $svg = QrCodeGenerator::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrCode->url);

        return response($svg)->header('Content-Type', 'image/svg+xml');
    }
    
    /**
     * Exporta uno o más códigos QR en el formato solicitado.
     */
    public function exportQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_ids'   => 'required|array',
            'qr_ids.*' => 'exists:qr_codes,id',
            'format'   => ['required', Rule::in(['png', 'svg', 'pdf', 'zip'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        $qrCodes = \App\Models\QrCode::whereIn('id', $request->qr_ids)
            ->where('business_id', $user->business_id)
            ->with('table') // Cargar la info de la mesa para el nombre del archivo
            ->get();
            
        if ($qrCodes->isEmpty() || $qrCodes->count() !== count($request->qr_ids)) {
            return response()->json(['message' => 'Uno o más códigos QR no se encontraron o no pertenecen a tu negocio.'], 403);
        }
        
        // Caso 1: Un solo QR en formato PNG, SVG o PDF
        if ($qrCodes->count() === 1 && in_array($request->format, ['png', 'svg', 'pdf'])) {
            $qr = $qrCodes->first();
            $fileName = 'mesa-' . ($qr->table->number ?? 'desconocida') . '-qr.' . $request->format;

            if ($request->format === 'pdf') {
                // Generamos el PNG internamente y lo incrustamos en un PDF
                $pngContent = QrCodeGenerator::format('png')
                    ->size(512)
                    ->margin(2)
                    ->errorCorrection('H')
                    ->generate($qr->url);

                $base64     = base64_encode($pngContent);
                $html       = '<html><body style="text-align:center;"><img src="data:image/png;base64,' . $base64 . '" style="width:70%;max-width:400px;" /></body></html>';

                $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html)->setPaper('a4', 'portrait')->output();

                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            }

            // Para PNG o SVG seguimos como antes
            $fileContent = QrCodeGenerator::format($request->format)
                ->size(512)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($qr->url);

            $contentType = $request->format === 'png' ? 'image/png' : 'image/svg+xml';

            return response($fileContent)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        }

        // Caso 2: Múltiples QR en PDF
        if ($qrCodes->count() > 1 && $request->format === 'pdf') {
            $html = '<html><body style="text-align:center;">';

            foreach ($qrCodes as $qr) {
                $pngContent = QrCodeGenerator::format('png')->size(512)->margin(2)->errorCorrection('H')->generate($qr->url);
                $base64     = base64_encode($pngContent);
                $tableNum   = $qr->table->number ?? 'desconocida';
                $html      .= '<div style="page-break-inside:avoid; margin-bottom:40px;">';
                $html      .= '<h2>Mesa ' . $tableNum . '</h2>';
                $html      .= '<img src="data:image/png;base64,' . $base64 . '" style="width:60%;max-width:350px;" />';
                $html      .= '</div>';
            }

            $html .= '</body></html>';

            $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html)->setPaper('a4', 'portrait')->output();
            $fileName   = 'qrcodes-' . uniqid() . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        }

        // Caso 3: Múltiples QR o formato ZIP -> Crear un archivo ZIP
        $zip = new ZipArchive();
        $zipFileName = 'qrcodes-' . uniqid() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Asegurarse de que el directorio temporal exista
        if (!Storage::exists('temp')) {
            Storage::makeDirectory('temp');
        }

        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return response()->json(['message' => 'No se pudo crear el archivo ZIP.'], 500);
        }

        foreach ($qrCodes as $qr) {
            $fileName = 'mesa-' . ($qr->table->number ?? 'desconocida') . '-qr.png';
            $fileContent = QrCodeGenerator::format('png')->size(512)->margin(2)->errorCorrection('H')->generate($qr->url);
            $zip->addFromString($fileName, $fileContent);
        }
        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Envía uno o más códigos QR por correo electrónico.
     */
    public function emailQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_ids'     => 'required|array',
            'qr_ids.*'   => 'exists:qr_codes,id',
            'format'     => ['required', Rule::in(['png', 'pdf'])],
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'subject'    => 'sometimes|string|max:255',
            'body'       => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user     = Auth::user();
        $qrCodes  = \App\Models\QrCode::whereIn('id', $request->qr_ids)
            ->where('business_id', $user->business_id)
            ->with('table')
            ->get();

        if ($qrCodes->isEmpty()) {
            return response()->json(['message' => 'No se encontraron los códigos QR solicitados o no pertenecen a tu negocio.'], 403);
        }

        // Generar el/los archivos que se adjuntarán
        $attachments = [];

        // Reutilizamos la lógica de exportación: si es un único QR simplemente generamos adjunto único.
        if ($qrCodes->count() === 1) {
            $qr = $qrCodes->first();
            $fileName = 'mesa-' . ($qr->table->number ?? 'desconocida') . '-qr.' . $request->format;

            if ($request->format === 'pdf') {
                $pngContent = QrCodeGenerator::format('png')->size(512)->margin(2)->errorCorrection('H')->generate($qr->url);
                $base64     = base64_encode($pngContent);
                $html       = '<html><body style="text-align:center;"><img src="data:image/png;base64,' . $base64 . '" style="width:70%;max-width:400px;" /></body></html>';
                $fileData   = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html)->setPaper('a4', 'portrait')->output();
                $mime       = 'application/pdf';
            } else {
                $fileData = QrCodeGenerator::format('png')->size(512)->margin(2)->errorCorrection('H')->generate($qr->url);
                $mime     = 'image/png';
            }

            $attachments[] = [
                'data' => $fileData,
                'name' => $fileName,
                'mime' => $mime,
            ];
        } else {
            // Múltiples QR -> Generamos un PDF multi-página o un ZIP, dependiendo del formato
            if ($request->format === 'pdf') {
                $html = '<html><body style="text-align:center;">';
                foreach ($qrCodes as $qr) {
                    $pngContent = QrCodeGenerator::format('png')->size(512)->margin(2)->errorCorrection('H')->generate($qr->url);
                    $base64     = base64_encode($pngContent);
                    $tableNum   = $qr->table->number ?? 'desconocida';
                    $html      .= '<div style="page-break-inside:avoid; margin-bottom:40px;">';
                    $html      .= '<h2>Mesa ' . $tableNum . '</h2>';
                    $html      .= '<img src="data:image/png;base64,' . $base64 . '" style="width:60%;max-width:350px;" />';
                    $html      .= '</div>';
                }
                $html       .= '</body></html>';
                $pdfContent  = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html)->setPaper('a4', 'portrait')->output();

                $attachments[] = [
                    'data' => $pdfContent,
                    'name' => 'qrcodes-' . uniqid() . '.pdf',
                    'mime' => 'application/pdf',
                ];
            } else {
                // Formato PNG con varios -> ZIP
                $zip = new ZipArchive();
                $zipFileName = 'qrcodes-' . uniqid() . '.zip';
                $zipPath = storage_path('app/temp/' . $zipFileName);

                if (!Storage::exists('temp')) {
                    Storage::makeDirectory('temp');
                }

                if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                    return response()->json(['message' => 'No se pudo crear el archivo ZIP para adjuntar.'], 500);
                }

                foreach ($qrCodes as $qr) {
                    $fileName = 'mesa-' . ($qr->table->number ?? 'desconocida') . '-qr.png';
                    $fileContent = QrCodeGenerator::format('png')->size(512)->margin(2)->errorCorrection('H')->generate($qr->url);
                    $zip->addFromString($fileName, $fileContent);
                }
                $zip->close();

                $zipData = file_get_contents($zipPath);
                $attachments[] = [
                    'data' => $zipData,
                    'name' => $zipFileName,
                    'mime' => 'application/zip',
                ];

                // Limpiamos el archivo temporal
                unlink($zipPath);
            }
        }

        // Enviar correo
        $subject = $request->get('subject', 'Códigos QR');
        $body    = $request->get('body', 'Adjuntamos los códigos QR solicitados.');

        // Preparar datos para la vista
        $businessName = $user->business->name ?? config('app.name', 'Mozo App');
        $introText    = $body;

        // Generar un array con los códigos base64 para previsualizar dentro del correo
        $embeddedQrCodes = [];
        foreach ($qrCodes as $qr) {
            $pngContent = QrCodeGenerator::format('png')->size(220)->margin(1)->errorCorrection('H')->generate($qr->url);
            $embeddedQrCodes[] = [
                'table_number' => $qr->table->number ?? 'N/A',
                'base64'       => base64_encode($pngContent),
            ];
        }

        \Illuminate\Support\Facades\Mail::send('emails.qr_codes', [
            'businessName' => $businessName,
            'introText'    => $introText,
            'qrCodes'      => $embeddedQrCodes,
            'subject'      => $subject,
        ], function ($message) use ($request, $attachments, $subject, $businessName) {
            $message->to($request->recipients)
                    ->subject($subject)
                    ->from(config('mail.from.address'), $businessName)
                    ->replyTo(config('mail.from.address'), $businessName);

            foreach ($attachments as $attachment) {
                $message->attachData($attachment['data'], $attachment['name'], ['mime' => $attachment['mime']]);
            }
        });

        return response()->json([
            'message' => 'Correo(s) enviado(s) exitosamente',
            'recipients' => $request->recipients,
        ]);
    }
}
