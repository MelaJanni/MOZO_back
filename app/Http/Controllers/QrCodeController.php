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
use App\Services\QrCodeService;
use App\Services\QrGeneratorService;
use App\Http\Controllers\Concerns\ResolvesActiveBusiness;
use App\Http\Controllers\Concerns\JsonResponses;

class QrCodeController extends Controller
{
    use ResolvesActiveBusiness, JsonResponses;
    
    protected QrCodeService $service;
    protected QrGeneratorService $qrGenerator;

    public function __construct(QrCodeService $service, QrGeneratorService $qrGenerator)
    {
        $this->service = $service;
        $this->qrGenerator = $qrGenerator;
    }

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

    public function store(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
        ]);

        $codeData = $this->generateUniqueCode();

        $qrCode = QrCode::create([
            'table_id' => $request->table_id,
            'code_data' => $codeData,
        ]);

        return response()->json($qrCode, 201);
    }

    public function show(QrCode $qrCode)
    {
        return response()->json($qrCode);
    }

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

    public function destroy(QrCode $qrCode)
    {
        $qrCode->delete();
        return response()->json(null, 204);
    }

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

    public static function generateForTable(Table $table): QrCode
    {
        // Mantener compatibilidad llamando al servicio
        return app(QrCodeService::class)->generateForTable($table);
    }

    public function generateQRCode(Request $request, $tableId)
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $table = Table::where('id', $tableId)
            ->where('business_id', $request->business_id)
            ->firstOrFail();
        $force = request()->boolean('regenerate', false);
        $qrCode = $this->service->generateForTable($table, $force);
        return response()->json([
            'message' => $force ? 'Código QR regenerado exitosamente' : 'Código QR generado/actualizado exitosamente',
            'qr_code' => $qrCode,
        ]);
    }

    public function preview(Request $request, $tableId)
    {
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $table = Table::where('id', $tableId)
            ->where('business_id', $request->business_id)
            ->firstOrFail();

        $qrCode = $table->qrCode;

        if (!$qrCode) {
            return response()->json(['message' => 'Esta mesa aún no tiene un QR generado.'], 404);
        }

        try {
            $svg = $this->qrGenerator->generate($qrCode->url, 'svg', 300);
            return response($svg)->header('Content-Type', 'image/svg+xml');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate QR preview',
                'message' => $e->getMessage(),
                'imagick_available' => $this->qrGenerator->isImageMagickAvailable(),
                'available_formats' => $this->qrGenerator->getAvailableFormats()
            ], 500);
        }
    }
    
    public function regenerateMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_ids' => 'required|array',
            'table_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $tableIds = array_unique($request->table_ids);
        // Restringir a un tamaño razonable
        if (count($tableIds) > 200) {
            return response()->json([
                'message' => 'Demasiadas mesas solicitadas. Máximo 200 por solicitud.'
            ], 422);
        }

        $tables = Table::whereIn('id', $tableIds)
            ->where('business_id', $request->business_id)
            ->get();

        if ($tables->isEmpty()) {
            return response()->json(['message' => 'No se encontraron mesas válidas para tu negocio.'], 404);
        }

        $regenerated = [];
        $failed = [];

        foreach ($tables as $table) {
            try {
                $qr = $this->service->generateForTable($table, true);
                $regenerated[] = [
                    'table_id' => $table->id,
                    'number' => $table->number,
                    'qr_id' => $qr->id,
                    'code' => $qr->code,
                    'url' => $qr->url,
                ];
            } catch (\Throwable $e) {
                $failed[] = [
                    'table_id' => $table->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Proceso de regeneración en lote finalizado',
            'regenerated_count' => count($regenerated),
            'failed_count' => count($failed),
            'regenerated' => $regenerated,
            'failed' => $failed,
        ]);
    }
    
    public function exportQR(Request $request)
    {
        // Check PNG capability and adjust allowed formats
        $allowedFormats = ['svg']; // SVG always works
        if ($this->qrGenerator->canGeneratePng()) {
            $allowedFormats = array_merge($allowedFormats, ['png', 'pdf', 'zip']);
        }

        $validator = Validator::make($request->all(), [
            'qr_ids'   => 'required|array',
            'qr_ids.*' => 'exists:qr_codes,id',
            'format'   => ['required', Rule::in($allowedFormats)],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            
            // Add helpful message about PNG capability if format is not allowed
            if ($errors->has('format') && !$this->qrGenerator->canGeneratePng()) {
                $systemStatus = $this->qrGenerator->getSystemStatus();
                return response()->json([
                    'errors' => $errors,
                    'message' => 'PNG generation not available. Only SVG format is supported.',
                    'available_formats' => $allowedFormats,
                    'system_status' => $systemStatus,
                    'install_help' => 'Install ImageMagick or ensure GD extension is available for PNG/PDF support.'
                ], 422);
            }
            
            return response()->json(['errors' => $errors], 422);
        }
        
        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $qrCodes = \App\Models\QrCode::whereIn('id', $request->qr_ids)
            ->where('business_id', $request->business_id)
            ->with('table')
            ->get();
            
        if ($qrCodes->isEmpty() || $qrCodes->count() !== count($request->qr_ids)) {
            return response()->json(['message' => 'Uno o más códigos QR no se encontraron o no pertenecen a tu negocio.'], 403);
        }
        
        if ($qrCodes->count() === 1 && in_array($request->format, ['png', 'svg', 'pdf'])) {
            $qr = $qrCodes->first();
            $fileName = 'mesa-' . ($qr->table->number ?? 'desconocida') . '-qr.' . $request->format;

            if ($request->format === 'pdf') {
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

        $zip = new ZipArchive();
        $zipFileName = 'qrcodes-' . uniqid() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

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

    public function emailQR(Request $request)
    {
        // Check PNG capability for email formats
        $allowedEmailFormats = [];
        if ($this->qrGenerator->canGeneratePng()) {
            $allowedEmailFormats = ['png', 'pdf'];
        }

        $validator = Validator::make($request->all(), [
            'qr_ids'     => 'required|array',
            'qr_ids.*'   => 'exists:qr_codes,id',
            'format'     => ['required', Rule::in($allowedEmailFormats)],
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'subject'    => 'sometimes|string|max:255',
            'body'       => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            
            if ($errors->has('format') && !$this->qrGenerator->canGeneratePng()) {
                return response()->json([
                    'errors' => $errors,
                    'message' => 'PNG generation required for email QR functionality.',
                    'system_status' => $this->qrGenerator->getSystemStatus(),
                    'install_help' => 'Install ImageMagick or ensure GD extension is available.'
                ], 422);
            }
            
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ✨ Middleware EnsureActiveBusiness ya inyectó business_id
        $qrCodes  = \App\Models\QrCode::whereIn('id', $request->qr_ids)
            ->where('business_id', $request->business_id)
            ->with('table')
            ->get();

        if ($qrCodes->isEmpty()) {
            return response()->json(['message' => 'No se encontraron los códigos QR solicitados o no pertenecen a tu negocio.'], 403);
        }

        $attachments = [];

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

                unlink($zipPath);
            }
        }

        $subject = $request->get('subject', 'Códigos QR');
        $body    = $request->get('body', 'Adjuntamos los códigos QR solicitados.');

        $businessName = $user->business->name ?? config('app.name', 'Mozo App');
        $introText    = $body;

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

    /**
     * Get available QR formats and system capabilities
     */
    public function getCapabilities()
    {
        $systemStatus = $this->qrGenerator->getSystemStatus();
        
        return response()->json([
            'system_status' => $systemStatus,
            'available_formats' => [
                'export' => $this->qrGenerator->canGeneratePng() 
                    ? ['svg', 'png', 'pdf', 'zip'] 
                    : ['svg'],
                'email' => $this->qrGenerator->canGeneratePng() 
                    ? ['png', 'pdf'] 
                    : [],
                'preview' => ['svg'] // Always available
            ],
            'recommendations' => [
                'svg' => 'Always available, scalable, smaller file size',
                'png' => 'Requires ImageMagick or GD, good for printing',
                'pdf' => 'Requires ImageMagick or GD, best for professional documents',
                'zip' => 'Requires ImageMagick or GD, for bulk downloads'
            ],
            'install_help' => $this->qrGenerator->canGeneratePng() 
                ? null 
                : 'Install ImageMagick extension or ensure GD is available for full QR functionality.'
        ]);
    }
}
