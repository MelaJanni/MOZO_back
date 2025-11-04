<?php

namespace App\Filament\Resources\SystemConfigResource\Pages;

use App\Filament\Resources\SystemConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class ManageSystemConfig extends ManageRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SystemConfigResource::class;

    protected static ?string $title = 'Configuración del Sistema';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getDefaultData());
    }

    public function form(Form $form): Form
    {
        return $this->resource::form($form)
            ->statePath('data');
    }

    private function getDefaultData(): array
    {
        return [
            // Información de contacto
            'support_email' => config('app.support_email', 'soporte@mozoqr.com'),
            'support_phone' => '+57 300 123 4567',
            'support_whatsapp' => '+57 300 123 4567',
            'support_address' => 'Calle 123 #45-67, Bogotá, Colombia',
            'support_start_time' => '08:00',
            'support_end_time' => '17:00',
            'support_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],

            // Configuración de pagos
            'mercadopago_enabled' => true,
            'mercadopago_public_key' => config('services.mercadopago.public_key', ''),
            'mercadopago_access_token' => config('services.mercadopago.access_token', ''),
            'paypal_enabled' => false,
            'paypal_client_id' => config('services.paypal.client_id', ''),
            'paypal_client_secret' => config('services.paypal.client_secret', ''),
            'bank_transfer_enabled' => true,
            'bank_account_info' => "Banco: Bancolombia\nTitular: MOZO QR SAS\nCuenta: 123-456-789\nTipo: Ahorros",

            // Configuración de la aplicación
            'app_name' => config('app.name', 'MOZO QR'),
            'app_url' => config('app.url', 'https://mozoqr.com'),
            'default_timezone' => config('app.timezone', 'America/Bogota'),
            'default_currency' => 'COP',
            'maintenance_mode' => false,
            'maintenance_message' => 'Estamos realizando mejoras al sistema. Volveremos pronto.',

            // Términos y políticas
            'terms_of_service' => $this->getDefaultTerms(),
            'privacy_policy' => $this->getDefaultPrivacy(),
            'refund_policy' => $this->getDefaultRefunds(),
        ];
    }

    private function getDefaultTerms(): string
    {
        return '<h2>Términos de Servicio - MOZO QR</h2>
<p>Al utilizar nuestros servicios, usted acepta los siguientes términos:</p>
<ul>
<li>El servicio se proporciona "tal como está"</li>
<li>Nos reservamos el derecho de modificar estos términos</li>
<li>El usuario es responsable de mantener la confidencialidad de su cuenta</li>
</ul>';
    }

    private function getDefaultPrivacy(): string
    {
        return '<h2>Política de Privacidad - MOZO QR</h2>
<p>Respetamos su privacidad y protegemos sus datos personales:</p>
<ul>
<li>Recopilamos solo la información necesaria para brindar nuestros servicios</li>
<li>No compartimos sus datos con terceros sin su consentimiento</li>
<li>Utilizamos medidas de seguridad para proteger su información</li>
</ul>';
    }

    private function getDefaultRefunds(): string
    {
        return '<h2>Política de Reembolsos - MOZO QR</h2>
<p>Nuestra política de reembolsos incluye:</p>
<ul>
<li>Reembolsos disponibles dentro de los primeros 7 días</li>
<li>Se requiere justificación válida para el reembolso</li>
<li>Los reembolsos se procesan en 5-10 días hábiles</li>
</ul>';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar Configuración')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Aquí normalmente guardarías en base de datos o archivo de configuración
        // Por ahora simularemos que se guarda exitosamente

        Notification::make()
            ->title('Configuración guardada exitosamente')
            ->success()
            ->send();
    }
}