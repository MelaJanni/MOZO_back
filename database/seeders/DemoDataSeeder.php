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
        // Negocios demo
        $biz = [
            ['code' => 'PLAZA1','name' => 'Restaurante La Plaza','email' => 'info@laplaza.com','address' => 'Av. Corrientes 1234, Buenos Aires','phone' => '+54 11 4567-8901','description' => 'Restaurante gourmet en el corazón de Buenos Aires'],
            ['code' => 'CAFE01','name' => 'Café Central','email' => 'contacto@cafecentral.com','address' => 'San Martín 567, Córdoba','phone' => '+54 351 123-4567','description' => 'Café boutique con ambiente acogedor'],
            ['code' => 'PIZZA3','name' => 'Pizza Express','email' => 'pedidos@pizzaexpress.com','address' => 'Rivadavia 890, Rosario','phone' => '+54 341 999-8888','description' => 'Pizzería de entrega rápida'],
        ];
        $business = [];
        foreach ($biz as $b) {
        $business[$b['code']] = Business::updateOrCreate(
                ['invitation_code' => $b['code']],
                [
            'name' => $b['name'],
            'address' => $b['address'],
            'phone' => $b['phone'],
            'email' => $b['email'],
            'description' => $b['description'],
                    'is_active' => true,
                ]
            );
        }

        // Usuarios demo
        $users = [
            ['email' => 'maria@example.com','name' => 'María González','roles' => ['super_admin']],
            ['email' => 'carlos@example.com','name' => 'Carlos Rodríguez','roles' => []],
            ['email' => 'ana@example.com','name' => 'Ana Martínez','roles' => []],
            ['email' => 'luis@example.com','name' => 'Luis García','roles' => []],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'],'password' => Hash::make('password')]
            );
            foreach ($u['roles'] as $role) {
                try { $user->assignRole($role); } catch (\Throwable $e) {}
            }

            AdminProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $user->name,
                    'position' => 'Administrador',
                    'corporate_phone' => '+54 11 0000-0000',
                ]
            );
            WaiterProfile::updateOrCreate(['user_id' => $user->id], ['display_name' => explode(' ', $user->name)[0]]);
        }

        // Roles en negocios
        $maria = User::where('email','maria@example.com')->first();
        $carlos = User::where('email','carlos@example.com')->first();
        $ana   = User::where('email','ana@example.com')->first();
        $luis  = User::where('email','luis@example.com')->first();

        $business['PLAZA1']->addAdmin($maria, 'owner');
        $business['CAFE01']->addAdmin($maria, 'manager');
    $business['PLAZA1']->addWaiter($maria, 'tiempo parcial', 25);

        $business['PIZZA3']->addAdmin($carlos, 'owner');
    $business['CAFE01']->addWaiter($carlos, 'por horas', 22);

    $business['PLAZA1']->addWaiter($ana, 'tiempo completo', 20);
    $business['PIZZA3']->addWaiter($ana, 'tiempo parcial', 18);

    $business['CAFE01']->addWaiter($luis, 'tiempo completo', 19);

        UserActiveRole::updateOrCreate(['user_id'=>$maria->id,'business_id'=>$business['PLAZA1']->id],['active_role'=>'admin']);
        UserActiveRole::updateOrCreate(['user_id'=>$maria->id,'business_id'=>$business['CAFE01']->id],['active_role'=>'admin']);
        UserActiveRole::updateOrCreate(['user_id'=>$carlos->id,'business_id'=>$business['PIZZA3']->id],['active_role'=>'admin']);
        UserActiveRole::updateOrCreate(['user_id'=>$carlos->id,'business_id'=>$business['CAFE01']->id],['active_role'=>'waiter']);
        UserActiveRole::updateOrCreate(['user_id'=>$ana->id,'business_id'=>$business['PLAZA1']->id],['active_role'=>'waiter']);
        UserActiveRole::updateOrCreate(['user_id'=>$ana->id,'business_id'=>$business['PIZZA3']->id],['active_role'=>'waiter']);
        UserActiveRole::updateOrCreate(['user_id'=>$luis->id,'business_id'=>$business['CAFE01']->id],['active_role'=>'waiter']);

        // Menú por defecto y luego Mesas y QR demo (regla: no crear mesas sin menú)
        $qrService = app(QrCodeService::class);
        $tablesSpec = [
            ['code'=>'PLAZA1','n'=>5],
            ['code'=>'CAFE01','n'=>3],
            ['code'=>'PIZZA3','n'=>4],
        ];

        foreach ($tablesSpec as $spec) {
            $bizModel = $business[$spec['code']];
            // Asegurar al menos un menú por negocio
            Menu::updateOrCreate(
                ['business_id' => $bizModel->id, 'is_default' => true],
                [
                    'name' => 'Menú Principal',
                    'file_path' => 'menus/demo-'.$spec['code'].'.pdf', // placeholder
                    'is_default' => true,
                    'display_order' => 1,
                ]
            );
            // Ahora sí, crear mesas
            for ($i=1; $i<=$spec['n']; $i++) {
                $table = Table::firstOrCreate(
                    ['business_id'=>$bizModel->id,'number'=>$i],
                    ['name'=>"Mesa $i",'capacity'=>4,'location'=>null,'status'=>'available','notifications_enabled'=>true]
                );
                try { $qrService->generateForTable($table); } catch (\Exception $e) {}
            }
        }

        // Suscripciones demo (fuente de verdad)
        $monthly = Plan::where('code','monthly')->first();
        foreach ([$maria,$carlos,$ana,$luis] as $u) {
            if (!$u || !$monthly) continue;
            $has = Subscription::where('user_id',$u->id)->whereIn('status',['active','in_trial'])->exists();
            if (!$has) {
                $sub = Subscription::create([
                    'user_id' => $u->id,
                    'plan_id' => $monthly->id,
                    'provider' => 'offline',
                    'status' => 'active',
                    'auto_renew' => false,
                    'current_period_end' => now()->addMonth(),
                    'metadata' => ['seeded' => true],
                ]);
                // Pago de cortesía (paid)
                Payment::create([
                    'subscription_id' => $sub->id,
                    'user_id' => $u->id,
                    'provider' => 'offline',
                    'provider_payment_id' => 'SEED-'.uniqid(),
                    'amount_cents' => $monthly->price_cents,
                    'currency' => $monthly->currency,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'raw_payload' => ['seeded'=>true],
                ]);
            }
        }

    // Mostrar credenciales de usuarios demo
    echo "\nUsuarios de prueba creados:\n";
    echo " - María González: maria@example.com / password (super_admin)\n";
    echo " - Carlos Rodríguez: carlos@example.com / password\n";
    echo " - Ana Martínez: ana@example.com / password\n";
    echo " - Luis García: luis@example.com / password\n\n";
    }
}
