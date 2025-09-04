<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use App\Models\WaiterProfile;
use App\Models\AdminProfile;
use App\Models\UserActiveRole;
use App\Models\Table;
use App\Services\QrCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "🏗️  Creando sistema multi-rol y multi-negocio...\n\n";

        // ========================================
        // 1. CREAR NEGOCIOS
        // ========================================
        
        $business1 = Business::updateOrCreate(
            ['invitation_code' => 'PLAZA1'],
            [
                'name' => 'Restaurante La Plaza',
                'address' => 'Av. Corrientes 1234, Buenos Aires',
                'phone' => '+54 11 4567-8901',
                'email' => 'info@laplaza.com',
                'description' => 'Restaurante gourmet en el corazón de Buenos Aires',
                'is_active' => true,
            ]
        );

        $business2 = Business::updateOrCreate(
            ['invitation_code' => 'CAFE01'],
            [
                'name' => 'Café Central',
                'address' => 'San Martín 567, Córdoba',
                'phone' => '+54 351 123-4567',
                'email' => 'contacto@cafecentral.com',
                'description' => 'Café boutique con ambiente acogedor',
                'is_active' => true,
            ]
        );

        $business3 = Business::updateOrCreate(
            ['invitation_code' => 'PIZZA3'],
            [
                'name' => 'Pizza Express',
                'address' => 'Rivadavia 890, Rosario',
                'phone' => '+54 341 999-8888',
                'email' => 'pedidos@pizzaexpress.com',
                'description' => 'Pizzería de entrega rápida',
                'is_active' => true,
            ]
        );

        echo "✅ Creados 3 negocios\n";

        // ========================================
        // 2. CREAR USUARIOS MULTI-ROL
        // ========================================

        // USUARIO 1: María - Admin de 2 negocios + Mozo en 1
        $maria = User::firstOrCreate(
            ['email' => 'maria@example.com'],
            [
                'name' => 'María González',
                'password' => Hash::make('password'),
            ]
        );

        // Membresía activa por defecto
        $maria->update([
            'membership_plan' => 'monthly',
            'membership_expires_at' => now()->addMonth(),
        ]);

        // Crear perfil de admin
        AdminProfile::updateOrCreate(
            ['user_id' => $maria->id],
            [
                'display_name' => 'María González',
                'position' => 'Gerente General',
                'corporate_phone' => '+54 11 4567-8901',
                'bio' => 'Gerente con 15 años de experiencia en hostelería',
            ]
        );

        // Crear perfil de mozo (para cuando trabaje como mozo)
        WaiterProfile::updateOrCreate(
            ['user_id' => $maria->id],
            [
                'display_name' => 'María',
                'phone' => '+54 11 4567-8901',
                'bio' => 'Gerente que también trabaja como mozo cuando es necesario',
                'experience_years' => 15,
                'gender' => 'femenino',
                'birth_date' => '1985-03-15',
                'height' => 1.65,
                'weight' => 60,
            ]
        );

        // Asignar como ADMIN a 2 negocios
        $business1->addAdmin($maria, 'owner');
        $business2->addAdmin($maria, 'manager');
        
        // Asignar como MOZO a 1 negocio (puede trabajar como mozo en su propio restaurante)
        $business1->addWaiter($maria, 'tiempo parcial', 25.00);

        // Establecer roles activos
    UserActiveRole::updateOrCreate(['user_id' => $maria->id, 'business_id' => $business1->id], ['active_role' => 'admin']);
    UserActiveRole::updateOrCreate(['user_id' => $maria->id, 'business_id' => $business2->id], ['active_role' => 'admin']);

        echo "✅ María: Admin en 2 negocios + Mozo en 1 (puede cambiar roles)\n";

        // USUARIO 2: Carlos - Admin de 1 negocio + Mozo en otro
        $carlos = User::firstOrCreate(
            ['email' => 'carlos@example.com'],
            [
                'name' => 'Carlos Rodríguez',
                'password' => Hash::make('password'),
            ]
        );

        $carlos->update([
            'membership_plan' => 'monthly',
            'membership_expires_at' => now()->addMonth(),
        ]);

        AdminProfile::updateOrCreate(
            ['user_id' => $carlos->id],
            [
                'display_name' => 'Carlos Rodríguez',
                'position' => 'Propietario',
                'corporate_phone' => '+54 341 999-8888',
                'bio' => 'Emprendedor gastronómico',
            ]
        );

        WaiterProfile::updateOrCreate(
            ['user_id' => $carlos->id],
            [
                'display_name' => 'Carlos',
                'phone' => '+54 341 999-8888',
                'bio' => 'Propietario que también atiende mesas',
                'experience_years' => 8,
                'gender' => 'masculino',
                'birth_date' => '1988-07-22',
                'height' => 1.78,
                'weight' => 75,
            ]
        );

        // Admin de Pizza Express, Mozo en Café Central
        $business3->addAdmin($carlos, 'owner');
        $business2->addWaiter($carlos, 'por horas', 22.00);

    UserActiveRole::updateOrCreate(['user_id' => $carlos->id, 'business_id' => $business3->id], ['active_role' => 'admin']);
    UserActiveRole::updateOrCreate(['user_id' => $carlos->id, 'business_id' => $business2->id], ['active_role' => 'waiter']);

        echo "✅ Carlos: Admin en Pizza Express + Mozo en Café Central\n";

        // USUARIO 3: Ana - Solo Mozo en múltiples negocios
        $ana = User::firstOrCreate(
            ['email' => 'ana@example.com'],
            [
                'name' => 'Ana Martínez',
                'password' => Hash::make('password'),
            ]
        );

        $ana->update([
            'membership_plan' => 'monthly',
            'membership_expires_at' => now()->addMonth(),
        ]);

        WaiterProfile::updateOrCreate(
            ['user_id' => $ana->id],
            [
                'display_name' => 'Ana Martínez',
                'phone' => '+54 11 8765-4321',
                'bio' => 'Mozo profesional especializada en servicio de mesa',
                'experience_years' => 5,
                'gender' => 'femenino',
                'birth_date' => '1995-11-08',
                'height' => 1.68,
                'weight' => 58,
                'skills' => ['servicio al cliente', 'sommelier', 'cocktails'],
            ]
        );

        // Trabaja en 2 negocios como mozo
        $business1->addWaiter($ana, 'tiempo completo', 20.00);
        $business3->addWaiter($ana, 'tiempo parcial', 18.00);

    UserActiveRole::updateOrCreate(['user_id' => $ana->id, 'business_id' => $business1->id], ['active_role' => 'waiter']);
    UserActiveRole::updateOrCreate(['user_id' => $ana->id, 'business_id' => $business3->id], ['active_role' => 'waiter']);

        echo "✅ Ana: Mozo en 2 negocios (La Plaza y Pizza Express)\n";

        // USUARIO 4: Luis - Solo Mozo en 1 negocio
        $luis = User::firstOrCreate(
            ['email' => 'luis@example.com'],
            [
                'name' => 'Luis García',
                'password' => Hash::make('password'),
            ]
        );

        $luis->update([
            'membership_plan' => 'monthly',
            'membership_expires_at' => now()->addMonth(),
        ]);

        WaiterProfile::updateOrCreate(
            ['user_id' => $luis->id],
            [
                'display_name' => 'Luis García',
                'phone' => '+54 351 555-1234',
                'bio' => 'Mozo especializado en café y postres',
                'experience_years' => 3,
                'gender' => 'masculino',
                'birth_date' => '1992-04-18',
                'height' => 1.75,
                'weight' => 70,
                'skills' => ['barista', 'repostería', 'latte art'],
            ]
        );

        $business2->addWaiter($luis, 'tiempo completo', 19.00);
    UserActiveRole::updateOrCreate(['user_id' => $luis->id, 'business_id' => $business2->id], ['active_role' => 'waiter']);

        echo "✅ Luis: Mozo en Café Central\n";

        // ========================================
        // 3. CREAR ALGUNAS MESAS CON QRS
        // ========================================

        $qrService = app(QrCodeService::class);

        // Mesas para La Plaza
        echo "Generando mesas y QRs para La Plaza...\n";
        for ($i = 1; $i <= 5; $i++) {
            $table = Table::firstOrCreate(
                [
                    'business_id' => $business1->id,
                    'number' => $i,
                ],
                [
                    'name' => "Mesa $i",
                    'capacity' => 4,
                    'location' => $i <= 2 ? 'Ventana' : 'Interior',
                    'status' => 'available',
                    'notifications_enabled' => true,
                ]
            );
            
            try {
                $qrService->generateForTable($table);
                echo "  ✅ Mesa $i con QR generado\n";
            } catch (\Exception $e) {
                echo "  ⚠️  Mesa $i creada, QR falló: " . $e->getMessage() . "\n";
            }
        }

        // Mesas para Café Central
        echo "Generando mesas y QRs para Café Central...\n";
        for ($i = 1; $i <= 3; $i++) {
            $table = Table::firstOrCreate(
                [
                    'business_id' => $business2->id,
                    'number' => $i,
                ],
                [
                    'name' => "Mesa $i",
                    'capacity' => 2,
                    'location' => 'Principal',
                    'status' => 'available',
                    'notifications_enabled' => true,
                ]
            );
            
            try {
                $qrService->generateForTable($table);
                echo "  ✅ Mesa $i con QR generado\n";
            } catch (\Exception $e) {
                echo "  ⚠️  Mesa $i creada, QR falló: " . $e->getMessage() . "\n";
            }
        }

        // Mesas para Pizza Express
        echo "Generando mesas y QRs para Pizza Express...\n";
        for ($i = 1; $i <= 4; $i++) {
            $table = Table::firstOrCreate(
                [
                    'business_id' => $business3->id,
                    'number' => $i,
                ],
                [
                    'name' => "Mesa $i",
                    'capacity' => 6,
                    'location' => 'Salón Principal',
                    'status' => 'available',
                    'notifications_enabled' => true,
                ]
            );
            
            try {
                $qrService->generateForTable($table);
                echo "  ✅ Mesa $i con QR generado\n";
            } catch (\Exception $e) {
                echo "  ⚠️  Mesa $i creada, QR falló: " . $e->getMessage() . "\n";
            }
        }

        echo "✅ Creadas mesas con QRs para todos los negocios\n";

        // ========================================
        // 4. FORZAR MEMBRESÍA ACTIVA PARA TODOS LOS USUARIOS (si o si)
        // ========================================
        echo "\n🔒 Forzando membresía activa para todos los usuarios...\n";
        \App\Models\User::query()->update([
            'membership_plan' => 'monthly',
            'membership_expires_at' => now()->addMonth(),
        ]);
        echo "✅ Todos los usuarios tienen membresía activa (monthly)\n\n";

        // ========================================
        // RESUMEN
        // ========================================

        echo "\n🎉 SEEDER COMPLETADO - Sistema Multi-Rol\n";
        echo "==========================================\n";
        echo "📍 NEGOCIOS CREADOS:\n";
        echo "   • La Plaza (PLAZA1) - 5 mesas\n";
        echo "   • Café Central (CAFE01) - 3 mesas\n";
        echo "   • Pizza Express (PIZZA3) - 4 mesas\n\n";

        echo "👥 USUARIOS MULTI-ROL:\n";
        echo "   • María (maria@example.com):\n";
        echo "     - ADMIN: La Plaza (owner) + Café Central (manager)\n";
        echo "     - MOZO: La Plaza (puede cambiar rol)\n\n";

        echo "   • Carlos (carlos@example.com):\n";
        echo "     - ADMIN: Pizza Express (owner)\n";
        echo "     - MOZO: Café Central\n\n";

        echo "   • Ana (ana@example.com):\n";
        echo "     - MOZO: La Plaza + Pizza Express\n\n";

        echo "   • Luis (luis@example.com):\n";
        echo "     - MOZO: Café Central\n\n";

        echo "🔑 Password para todos: 'password'\n";
        echo "🔄 Los usuarios pueden cambiar roles usando el método switchRole()\n";
    }
}