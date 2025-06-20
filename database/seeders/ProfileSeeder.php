<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar usuarios mozos
        $waiters = User::where('role', 'waiter')->get();
        
        foreach ($waiters as $waiter) {
            // Obtener mesas del negocio del mozo
            $tables = Table::where('business_id', $waiter->active_business_id)->get();
            
            if ($tables->count() > 0) {
                // Crear un único perfil para el usuario si no tiene uno
                $profile = Profile::firstOrCreate(
                    ['user_id' => $waiter->id],
                    ['name' => 'Perfil Principal']
                );

                // Asignar algunas mesas al perfil (lógica de ejemplo)
                $tablesToAssign = $tables->take(5); // Asigna las primeras 5 mesas
                $profile->tables()->syncWithoutDetaching($tablesToAssign->pluck('id'));
            }
        }
    }
} 