<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Business;
use App\Models\AdminProfile;

class BusinessSetup extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.business-setup';
    protected static ?string $title = 'Configuración de Negocio';
    protected static bool $shouldRegisterNavigation = false; // Hidden from navigation

    public ?array $adminData = [];
    public ?array $businessData = [];

    public function mount(): void
    {
        $user = auth()->user();

        // Load admin profile data
        $adminProfile = $user->adminProfile;
        $this->adminData = [
            'position' => $adminProfile?->position ?? '',
            'corporate_phone' => $adminProfile?->corporate_phone ?? '',
        ];

        // Load business data (first business or create new)
        $business = $user->businessesAsAdmin()->where('business_admins.is_active', true)->first();
        if ($business) {
            $this->businessData = [
                'name' => $business->name ?? '',
                'address' => $business->address ?? '',
                'phone' => $business->phone ?? '',
                'description' => $business->description ?? '',
            ];
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Perfil de Administrador')
                    ->description('Complete su información como administrador del sistema.')
                    ->schema([
                        Forms\Components\TextInput::make('adminData.position')
                            ->label('Cargo/Posición')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('adminData.corporate_phone')
                            ->label('Teléfono Corporativo')
                            ->required()
                            ->tel()
                            ->maxLength(20),
                    ]),

                Forms\Components\Section::make('Información del Negocio')
                    ->description('Configure los datos básicos de su negocio.')
                    ->schema([
                        Forms\Components\TextInput::make('businessData.name')
                            ->label('Nombre del Negocio')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('businessData.address')
                            ->label('Dirección')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('businessData.phone')
                            ->label('Teléfono')
                            ->required()
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Textarea::make('businessData.description')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(1000),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar y Continuar')
                ->action('save'),
        ];
    }

    public function save()
    {
        $data = $this->form->getState();
        $user = auth()->user();

        try {
            // Save admin profile
            AdminProfile::updateOrCreate(
                ['user_id' => $user->id],
                $data['adminData']
            );

            // Save business data
            $business = $user->businessesAsAdmin()->where('business_admins.is_active', true)->first();

            if ($business) {
                $business->update($data['businessData']);
            } else {
                // Create new business and associate user as admin
                $business = Business::create($data['businessData']);
                $user->businessesAsAdmin()->attach($business->id, [
                    'permission_level' => 'owner',
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }

            Notification::make()
                ->title('Configuración guardada exitosamente')
                ->success()
                ->send();

            // Check if setup is complete
            if ($this->isSetupComplete($user)) {
                return redirect()->route('filament.admin.pages.dashboard');
            } else {
                Notification::make()
                    ->title('Complete la configuración creando al menos un menú para su negocio')
                    ->warning()
                    ->send();

                return redirect()->route('filament.admin.resources.menus.index');
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al guardar la configuración')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function isSetupComplete($user): bool
    {
        $adminProfile = $user->adminProfile;
        if (!$adminProfile || !$adminProfile->isComplete()) {
            return false;
        }

        $businesses = $user->businessesAsAdmin()->where('business_admins.is_active', true)->get();

        foreach ($businesses as $business) {
            if (!$business->name || !$business->address || !$business->phone || !$business->description) {
                return false;
            }

            if ($business->menus()->count() === 0) {
                return false;
            }
        }

        return true;
    }
}