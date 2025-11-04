<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Coupon;
use App\Models\PaymentMethod;
use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PublicCheckoutPage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.pages.public-checkout';
    protected static ?string $title = 'Checkout - MOZO QR';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public ?Plan $plan = null;
    public ?array $paymentMethods = [];
    public ?Coupon $appliedCoupon = null;

    public function mount($planId = null): void
    {
        if ($planId) {
            $this->plan = Plan::active()->findOrFail($planId);
        }

        $this->paymentMethods = PaymentMethod::active()->ordered()->get()->toArray();

        $this->form->fill([
            'plan_id' => $this->plan?->id,
            'billing_period' => 'monthly',
            'payment_method' => 'mercadopago',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email')
                            ->maxLength(255),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Contraseña')
                                    ->password()
                                    ->required()
                                    ->rule(Password::default())
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),

                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Confirmar Contraseña')
                                    ->password()
                                    ->required()
                                    ->same('password')
                                    ->dehydrated(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Plan Seleccionado')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::active()->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state) => $this->refreshPlan($state)),

                        Forms\Components\Radio::make('billing_period')
                            ->label('Período de Facturación')
                            ->options([
                                'monthly' => 'Mensual',
                                'quarterly' => 'Trimestral (-10%)',
                                'yearly' => 'Anual (-20%)',
                            ])
                            ->required()
                            ->reactive(),
                    ]),

                Forms\Components\Section::make('Cupón de Descuento')
                    ->schema([
                        Forms\Components\TextInput::make('coupon_code')
                            ->label('Código de Cupón (Opcional)')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('apply_coupon')
                                    ->label('Aplicar')
                                    ->icon('heroicon-m-check')
                                    ->action('applyCoupon')
                            ),
                    ]),

                Forms\Components\Section::make('Método de Pago')
                    ->schema([
                        Forms\Components\Radio::make('payment_method')
                            ->label('Selecciona tu método de pago')
                            ->options([
                                'mercadopago' => 'Tarjeta de Crédito/Débito (Mercado Pago)',
                                'bank_transfer' => 'Transferencia Bancaria',
                            ])
                            ->descriptions([
                                'mercadopago' => 'Pago seguro con tarjeta de crédito o débito',
                                'bank_transfer' => 'Pago por transferencia bancaria (demora 24-48hs)',
                            ])
                            ->required(),
                    ]),

                Forms\Components\Checkbox::make('terms')
                    ->label('Acepto los Términos de Servicio y Política de Privacidad')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function refreshPlan($planId): void
    {
        if ($planId) {
            $this->plan = Plan::find($planId);
        }
    }

    public function applyCoupon(): void
    {
        $couponCode = $this->data['coupon_code'] ?? null;

        if (!$couponCode) {
            Notification::make()
                ->title('Error')
                ->body('Ingresa un código de cupón')
                ->danger()
                ->send();
            return;
        }

        $coupon = Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->first();

        if (!$coupon || !$coupon->isValid()) {
            Notification::make()
                ->title('Cupón inválido')
                ->body('El cupón no es válido o ha expirado')
                ->danger()
                ->send();
            return;
        }

        $this->appliedCoupon = $coupon;

        Notification::make()
            ->title('Cupón aplicado')
            ->body($coupon->getDiscountDescription())
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('register')
                ->label('Completar Registro y Pago')
                ->color('primary')
                ->size('lg')
                ->icon('heroicon-m-lock-closed')
                ->action('register'),
        ];
    }

    public function register(): void
    {
        $data = $this->form->getState();

        DB::beginTransaction();

        try {
            // Crear usuario
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);

            $plan = Plan::findOrFail($data['plan_id']);

            // Calcular precio
            $basePrice = $plan->getPriceWithDiscount($data['billing_period']);
            $finalPrice = $this->appliedCoupon ?
                $plan->getDiscountedPrice($this->appliedCoupon) :
                $basePrice;

            // Crear suscripción
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'billing_period' => $data['billing_period'],
                'price_at_creation' => $finalPrice,
                'currency' => 'ARS',
                'trial_ends_at' => $plan->hasTrialEnabled() ?
                    now()->addDays($plan->getTrialDays()) : null,
                'next_billing_date' => $this->calculateNextBillingDate(
                    $data['billing_period'],
                    $plan->hasTrialEnabled() ? $plan->getTrialDays() : 0
                ),
                'coupon_id' => $this->appliedCoupon?->id,
                'metadata' => [
                    'registration_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            // Usar cupón si existe
            if ($this->appliedCoupon) {
                $this->appliedCoupon->increment('redeemed_count');
            }

            // Procesar pago según método
            if ($data['payment_method'] === 'mercadopago') {
                $mercadoPagoService = app(MercadoPagoService::class);

                $preference = $mercadoPagoService->createPreference([
                    'title' => "Suscripción {$plan->name}",
                    'quantity' => 1,
                    'unit_price' => $finalPrice,
                    'currency_id' => 'ARS',
                    'external_reference' => $subscription->id,
                    'payer' => [
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'back_urls' => [
                        'success' => route('public.checkout.success'),
                        'failure' => route('public.checkout.cancel'),
                        'pending' => route('public.checkout.success'),
                    ],
                    'auto_return' => 'approved',
                    'notification_url' => route('webhooks.mercadopago'),
                ]);

                $subscription->update([
                    'provider_subscription_id' => $preference['id'],
                    'metadata' => array_merge($subscription->metadata ?? [], [
                        'mercadopago_preference_id' => $preference['id'],
                    ])
                ]);

                DB::commit();

                // Redirigir a Mercado Pago
                redirect($preference['init_point']);
                return;
            }

            if ($data['payment_method'] === 'bank_transfer') {
                $subscription->update(['status' => 'pending_bank_transfer']);
                DB::commit();

                // Redirigir a página de transferencia
                redirect()->route('public.checkout.bank-transfer', $subscription->id);
                return;
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error')
                ->body('Ocurrió un error procesando tu registro. Intenta nuevamente.')
                ->danger()
                ->send();
        }
    }

    private function calculateNextBillingDate($period, $trialDays = 0)
    {
        $startDate = $trialDays > 0 ? now()->addDays($trialDays) : now();

        return match($period) {
            'monthly' => $startDate->addMonth(),
            'quarterly' => $startDate->addMonths(3),
            'yearly' => $startDate->addYear(),
            default => $startDate->addMonth(),
        };
    }

    protected function getViewData(): array
    {
        return [
            'plan' => $this->plan,
            'appliedCoupon' => $this->appliedCoupon,
        ];
    }
}