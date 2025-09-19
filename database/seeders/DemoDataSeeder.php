<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{User,Business,AdminProfile,WaiterProfile,UserActiveRole,Table,Plan,Subscription,Payment,Menu};
use App\Services\QrCodeService;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "🔄 Siguiendo flujo correcto: Usuario → Plan → Negocio → Admin → Staff\n\n";

        // PASO 1: Crear usuarios demo (se registran primero)
        $userData = [
            [
                'email' => 'maria@example.com',
                'name' => 'María González',
                'plan_code' => 'PROFESSIONAL', // Plan profesional
                'business' => ['name' => 'Restaurante La Plaza','email' => 'info@laplaza.com','address' => 'Av. Corrientes 1234, Buenos Aires','phone' => '+54 11 4567-8901','description' => 'Restaurante gourmet en el corazón de Buenos Aires'],
                'roles' => ['super_admin']
            ],
            [
                'email' => 'carlos@example.com',
                'name' => 'Carlos Rodríguez',
                'plan_code' => 'STARTER', // Plan inicial
                'business' => ['name' => 'Café Central','email' => 'contacto@cafecentral.com','address' => 'San Martín 567, Córdoba','phone' => '+54 351 123-4567','description' => 'Café boutique con ambiente acogedor'],
                'roles' => []
            ],
            [
                'email' => 'ana@example.com',
                'name' => 'Ana Martínez',
                'plan_code' => 'ENTERPRISE', // Plan empresarial
                'business' => ['name' => 'Pizza Express','email' => 'pedidos@pizzaexpress.com','address' => 'Rivadavia 890, Rosario','phone' => '+54 341 999-8888','description' => 'Pizzería de entrega rápida'],
                'roles' => []
            ],
        ];

        $createdBusinesses = [];

        foreach ($userData as $data) {
            echo "👤 Creando usuario: {$data['name']}\n";

            // PASO 1: Crear usuario
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Asignar roles de sistema
            foreach ($data['roles'] as $role) {
                try {
                    $user->assignRole($role);
                    echo "  ✅ Rol asignado: {$role}\n";
                } catch (\Throwable $e) {}
            }

            // PASO 2: Contratar plan (suscripción)
            $plan = Plan::where('code', $data['plan_code'])->first();
            if ($plan) {
                echo "  💳 Contratando plan: {$plan->name}\n";

                $subscription = Subscription::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'billing_period' => 'monthly',
                        'price_at_creation' => $plan->price_ars,
                        'currency' => $plan->currency,
                        'trial_ends_at' => null,
                        'next_billing_date' => now()->addMonth(),
                        'provider' => 'demo',
                        'metadata' => ['seeded' => true, 'demo_user' => true],
                    ]
                );

                // Crear pago demo
                Payment::create([
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'amount' => $plan->price_ars,
                    'currency' => $plan->currency,
                    'status' => 'completed',
                    'provider' => 'demo',
                    'provider_payment_id' => 'DEMO-' . uniqid(),
                    'paid_at' => now(),
                    'metadata' => ['seeded' => true],
                ]);

                echo "  ✅ Suscripción creada y pagada\n";
            }

            // PASO 3: Usuario crea su negocio (ahora que tiene plan activo)
            echo "  🏢 Creando negocio: {$data['business']['name']}\n";

            $business = Business::create([
                'name' => $data['business']['name'],
                'email' => $data['business']['email'],
                'address' => $data['business']['address'],
                'phone' => $data['business']['phone'],
                'description' => $data['business']['description'],
                'owner_id' => $user->id, // El usuario es el dueño
                'invitation_code' => strtoupper(substr($data['business']['name'], 0, 6)),
                'is_active' => true,
            ]);

            $createdBusinesses[] = $business;

            // PASO 4: Usuario se convierte en admin del negocio
            echo "  👨‍💼 Convirtiendo usuario en admin del negocio\n";

            $business->addAdmin($user, 'owner');

            // Crear perfil de admin
            AdminProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $user->name,
                    'position' => 'Dueño/Administrador',
                    'corporate_phone' => $data['business']['phone'],
                ]
            );

            // Establecer rol activo
            UserActiveRole::updateOrCreate(
                ['user_id' => $user->id, 'business_id' => $business->id],
                ['active_role' => 'admin']
            );

            echo "  ✅ Usuario es ahora admin del negocio\n";

            // PASO 5: Crear menú por defecto y mesas
            echo "  📋 Creando menú por defecto\n";

            Menu::updateOrCreate(
                ['business_id' => $business->id, 'is_default' => true],
                [
                    'name' => 'Menú Principal',
                    'file_path' => 'menus/demo-' . $business->id . '.pdf',
                    'is_default' => true,
                    'display_order' => 1,
                ]
            );

            // Crear mesas según el plan
            $maxTables = $plan ? $plan->getMaxTables() : 5;
            $tablesToCreate = min($maxTables, 5); // Máximo 5 para demo

            echo "  🪑 Creando {$tablesToCreate} mesas\n";

            $qrService = app(QrCodeService::class);
            for ($i = 1; $i <= $tablesToCreate; $i++) {
                $table = Table::create([
                    'business_id' => $business->id,
                    'number' => $i,
                    'name' => "Mesa {$i}",
                    'capacity' => rand(2, 8),
                    'location' => null,
                    'status' => 'available',
                    'notifications_enabled' => true,
                ]);

                try {
                    $qrService->generateForTable($table);
                } catch (\Exception $e) {
                    echo "    ⚠️  Error generando QR para mesa {$i}: {$e->getMessage()}\n";
                }
            }

            echo "  ✅ Negocio completo configurado\n\n";
        }

        // PASO 6: Crear algunos mozos demo (empleados contratados por los admins)
        echo "👥 Creando mozos de ejemplo...\n";

        $waiterData = [
            ['email' => 'mozo1@example.com', 'name' => 'Juan Pérez', 'business_index' => 0], // Para María (Restaurante La Plaza)
            ['email' => 'mozo2@example.com', 'name' => 'Lucia Fernández', 'business_index' => 1], // Para Carlos (Café Central)
            ['email' => 'mozo3@example.com', 'name' => 'Pedro Martín', 'business_index' => 2], // Para Ana (Pizza Express)
        ];

        foreach ($waiterData as $index => $waiterInfo) {
            $business = $createdBusinesses[$waiterInfo['business_index']];
            $admin = User::find($business->owner_id);

            echo "  👨‍🍳 Creando mozo: {$waiterInfo['name']} para {$business->name}\n";

            // Crear usuario mozo
            $waiter = User::firstOrCreate(
                ['email' => $waiterInfo['email']],
                [
                    'name' => $waiterInfo['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Admin contrata al mozo
            $business->addWaiter($waiter, 'tiempo completo', rand(18, 25));

            // Crear perfil de mozo
            WaiterProfile::updateOrCreate(
                ['user_id' => $waiter->id],
                ['display_name' => explode(' ', $waiter->name)[0]]
            );

            // Establecer rol activo
            UserActiveRole::updateOrCreate(
                ['user_id' => $waiter->id, 'business_id' => $business->id],
                ['active_role' => 'waiter']
            );

            echo "  ✅ Mozo contratado por {$admin->name}\n";
        }

        echo "\n🎉 SEEDER COMPLETADO - Flujo correcto aplicado\n\n";

        // Mostrar credenciales de usuarios demo
        echo "👤 Usuarios de prueba creados:\n";
        echo "ADMINS (con negocios):\n";
        echo " - María González: maria@example.com / password (super_admin + Restaurante La Plaza)\n";
        echo " - Carlos Rodríguez: carlos@example.com / password (Café Central)\n";
        echo " - Ana Martínez: ana@example.com / password (Pizza Express)\n\n";
        echo "MOZOS (empleados):\n";
        echo " - Juan Pérez: mozo1@example.com / password (Mozo en La Plaza)\n";
        echo " - Lucia Fernández: mozo2@example.com / password (Mozo en Café Central)\n";
        echo " - Pedro Martín: mozo3@example.com / password (Mozo en Pizza Express)\n\n";
    }
}
