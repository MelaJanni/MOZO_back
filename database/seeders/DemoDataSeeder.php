<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{User,Business,AdminProfile,WaiterProfile,UserActiveRole,Table,Menu};
use App\Services\QrCodeService;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "🔄 Creando usuarios admin permanentes (sin suscripciones) → Negocios → Staff\n\n";

        // PASO 1: Crear usuarios admin permanentes (clientes VIP sin restricciones de planes)
        $userData = [
            [
                'email' => 'maria@example.com',
                'name' => 'María González',
                'business' => ['name' => 'Restaurante La Plaza','email' => 'info@laplaza.com','address' => 'Av. Corrientes 1234, Buenos Aires','phone' => '+54 11 4567-8901','description' => 'Restaurante gourmet en el corazón de Buenos Aires'],
                'roles' => ['super_admin']
            ],
            [
                'email' => 'carlos@example.com',
                'name' => 'Carlos Rodríguez',
                'business' => ['name' => 'Café Central','email' => 'contacto@cafecentral.com','address' => 'San Martín 567, Córdoba','phone' => '+54 351 123-4567','description' => 'Café boutique con ambiente acogedor'],
                'roles' => []
            ],
            [
                'email' => 'ana@example.com',
                'name' => 'Ana Martínez',
                'business' => ['name' => 'Pizza Express','email' => 'pedidos@pizzaexpress.com','address' => 'Rivadavia 890, Rosario','phone' => '+54 341 999-8888','description' => 'Pizzería de entrega rápida'],
                'roles' => []
            ],
        ];

        $createdBusinesses = [];

        foreach ($userData as $data) {
            echo "👤 Creando usuario admin permanente: {$data['name']}\n";

            // PASO 1: Crear usuario admin (sin restricciones de plan)
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_lifetime_paid' => true, // Cliente permanente
                ]
            );

            // Asegurar que el usuario tenga el flag de cliente permanente
            if (!$user->is_lifetime_paid) {
                $user->update(['is_lifetime_paid' => true]);
                echo "  ✅ Usuario actualizado como cliente permanente\n";
            }

            // Asignar roles de sistema
            foreach ($data['roles'] as $role) {
                try {
                    $user->assignRole($role);
                    echo "  ✅ Rol asignado: {$role}\n";
                } catch (\Throwable $e) {}
            }

            echo "  ✅ Usuario admin creado (sin suscripción, acceso permanente)\n";

            // PASO 2: Usuario crea su negocio (acceso directo, sin planes)
            echo "  🏢 Creando negocio: {$data['business']['name']}\n";

            $business = Business::firstOrCreate(
                ['invitation_code' => strtoupper(substr($data['business']['name'], 0, 6))],
                [
                    'name' => $data['business']['name'],
                    'email' => $data['business']['email'],
                    'address' => $data['business']['address'],
                    'phone' => $data['business']['phone'],
                    'description' => $data['business']['description'],
                    'is_active' => true,
                ]
            );

            $createdBusinesses[] = $business;

            // PASO 3: Usuario se convierte en admin del negocio
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

            // PASO 4: Crear menú por defecto y mesas (sin restricciones de plan)
            echo "  📋 Creando menú por defecto\n";

            Menu::updateOrCreate(
                ['business_id' => $business->id, 'is_default' => true],
                [
                    'name' => 'Menú Principal',
                    'file_path' => 'menus/676b846ee428a8ff4c3cf5df5c717112.pdf',
                    'is_default' => true,
                    'display_order' => 1,
                ]
            );

            // Crear mesas (usuarios admin tienen acceso completo)
            $tablesToCreate = 8; // Admin permanente puede crear las mesas que necesite

            echo "  🪑 Creando {$tablesToCreate} mesas (sin restricciones)\n";

            $qrService = app(QrCodeService::class);
            for ($i = 1; $i <= $tablesToCreate; $i++) {
                $table = Table::firstOrCreate(
                    ['business_id' => $business->id, 'number' => $i],
                    [
                        'name' => "Mesa {$i}",
                        'capacity' => rand(2, 8),
                        'location' => null,
                        'status' => 'available',
                        'notifications_enabled' => true,
                    ]
                );

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
            $admin = $business->admins()->first(); // Obtener el primer admin (que será el owner)

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

        echo "\n🎉 SEEDER COMPLETADO - Usuarios admin permanentes creados\n\n";

        // Mostrar credenciales de usuarios demo
        echo "👤 Usuarios de prueba creados:\n";
        echo "ADMINS PERMANENTES (sin suscripciones, acceso completo):\n";
        echo " - María González: maria@example.com / password (super_admin + Restaurante La Plaza)\n";
        echo " - Carlos Rodríguez: carlos@example.com / password (admin + Café Central)\n";
        echo " - Ana Martínez: ana@example.com / password (admin + Pizza Express)\n\n";
        echo "MOZOS (empleados contratados por los admins):\n";
        echo " - Juan Pérez: mozo1@example.com / password (Mozo en La Plaza)\n";
        echo " - Lucia Fernández: mozo2@example.com / password (Mozo en Café Central)\n";
        echo " - Pedro Martín: mozo3@example.com / password (Mozo en Pizza Express)\n\n";
        echo "📝 NOTA: Los usuarios admin no tienen restricciones de planes y pueden usar todas las funcionalidades.\n";
    }
}
