<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemConfigResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;

class SystemConfigResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configuración del Sistema';
    protected static ?string $modelLabel = 'Configuración';
    protected static ?string $pluralModelLabel = 'Configuraciones';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 1;

    // Este recurso no usa un modelo específico, sino configuraciones del sistema
    protected static ?string $model = null;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configuraciones')
                    ->tabs([
                        Tabs\Tab::make('Información de Contacto')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Datos de Contacto de Soporte')
                                    ->description('Información que se mostrará a los usuarios para contactar soporte')
                                    ->schema([
                                        Forms\Components\TextInput::make('support_email')
                                            ->label('Email de Soporte')
                                            ->email()
                                            ->required()
                                            ->default(config('app.support_email', 'soporte@mozoqr.com')),
                                        Forms\Components\TextInput::make('support_phone')
                                            ->label('Teléfono de Soporte')
                                            ->tel()
                                            ->required()
                                            ->default('+57 300 123 4567'),
                                        Forms\Components\TextInput::make('support_whatsapp')
                                            ->label('WhatsApp de Soporte')
                                            ->tel()
                                            ->helperText('Número con código de país (ej: +573001234567)'),
                                        Forms\Components\Textarea::make('support_address')
                                            ->label('Dirección Física')
                                            ->rows(3)
                                            ->placeholder('Calle 123 #45-67, Bogotá, Colombia'),
                                        Forms\Components\TimePicker::make('support_start_time')
                                            ->label('Horario de Atención - Inicio')
                                            ->default('08:00'),
                                        Forms\Components\TimePicker::make('support_end_time')
                                            ->label('Horario de Atención - Fin')
                                            ->default('17:00'),
                                        Forms\Components\CheckboxList::make('support_days')
                                            ->label('Días de Atención')
                                            ->options([
                                                'monday' => 'Lunes',
                                                'tuesday' => 'Martes',
                                                'wednesday' => 'Miércoles',
                                                'thursday' => 'Jueves',
                                                'friday' => 'Viernes',
                                                'saturday' => 'Sábado',
                                                'sunday' => 'Domingo',
                                            ])
                                            ->default(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                                            ->columns(3),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Configuración de Pagos')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Proveedores de Pago')
                                    ->description('Configuración de las pasarelas de pago')
                                    ->schema([
                                        Forms\Components\Toggle::make('mercadopago_enabled')
                                            ->label('MercadoPago Habilitado')
                                            ->default(true),
                                        Forms\Components\TextInput::make('mercadopago_public_key')
                                            ->label('MercadoPago - Clave Pública')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('mercadopago_access_token')
                                            ->label('MercadoPago - Access Token')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\Toggle::make('paypal_enabled')
                                            ->label('PayPal Habilitado')
                                            ->default(false),
                                        Forms\Components\TextInput::make('paypal_client_id')
                                            ->label('PayPal - Client ID')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('paypal_client_secret')
                                            ->label('PayPal - Client Secret')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\Toggle::make('bank_transfer_enabled')
                                            ->label('Transferencia Bancaria Habilitada')
                                            ->default(true),
                                        Forms\Components\Textarea::make('bank_account_info')
                                            ->label('Información de Cuenta Bancaria')
                                            ->rows(4)
                                            ->placeholder('Banco: Bancolombia\nTitular: MOZO QR SAS\nCuenta: 123-456-789\nTipo: Ahorros'),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Configuración de la Aplicación')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Section::make('Configuraciones Generales')
                                    ->schema([
                                        Forms\Components\TextInput::make('app_name')
                                            ->label('Nombre de la Aplicación')
                                            ->required()
                                            ->default('MOZO QR'),
                                        Forms\Components\TextInput::make('app_url')
                                            ->label('URL de la Aplicación')
                                            ->url()
                                            ->required()
                                            ->default('https://mozoqr.com'),
                                        Forms\Components\Select::make('default_timezone')
                                            ->label('Zona Horaria por Defecto')
                                            ->options([
                                                'America/Bogota' => 'Colombia (America/Bogota)',
                                                'America/Mexico_City' => 'México (America/Mexico_City)',
                                                'America/Lima' => 'Perú (America/Lima)',
                                                'America/Argentina/Buenos_Aires' => 'Argentina (America/Argentina/Buenos_Aires)',
                                            ])
                                            ->default('America/Bogota'),
                                        Forms\Components\Select::make('default_currency')
                                            ->label('Moneda por Defecto')
                                            ->options([
                                                'COP' => 'Peso Colombiano (COP)',
                                                'USD' => 'Dólar Americano (USD)',
                                                'MXN' => 'Peso Mexicano (MXN)',
                                                'PEN' => 'Sol Peruano (PEN)',
                                            ])
                                            ->default('COP'),
                                        Forms\Components\Toggle::make('maintenance_mode')
                                            ->label('Modo de Mantenimiento')
                                            ->helperText('Activar para mostrar página de mantenimiento a los usuarios'),
                                        Forms\Components\Textarea::make('maintenance_message')
                                            ->label('Mensaje de Mantenimiento')
                                            ->rows(3)
                                            ->default('Estamos realizando mejoras al sistema. Volveremos pronto.'),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Términos y Políticas')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Documentos Legales')
                                    ->schema([
                                        Forms\Components\RichEditor::make('terms_of_service')
                                            ->label('Términos de Servicio')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'bulletList',
                                                'orderedList',
                                                'link',
                                            ]),
                                        Forms\Components\RichEditor::make('privacy_policy')
                                            ->label('Política de Privacidad')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'bulletList',
                                                'orderedList',
                                                'link',
                                            ]),
                                        Forms\Components\RichEditor::make('refund_policy')
                                            ->label('Política de Reembolsos')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'bulletList',
                                                'orderedList',
                                                'link',
                                            ]),
                                    ])->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Este recurso no necesita tabla ya que es solo configuración
        return $table->columns([])->actions([])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSystemConfig::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}