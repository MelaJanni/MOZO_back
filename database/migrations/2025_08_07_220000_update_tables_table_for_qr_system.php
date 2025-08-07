<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            // Agregar columnas si no existen
            if (!Schema::hasColumn('tables', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('tables', 'code')) {
                $table->string('code')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tables', 'restaurant_id')) {
                $table->foreignId('restaurant_id')->nullable()->constrained()->onDelete('cascade')->after('business_id');
            }
            
            // Agregar índices si no existen
            if (!$this->indexExists('tables', 'tables_code_restaurant_id_unique')) {
                $table->unique(['code', 'restaurant_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropUnique(['code', 'restaurant_id']);
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn(['name', 'code', 'restaurant_id']);
        });
    }
    
    /**
     * Check if index exists
     */
    private function indexExists($table, $name)
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()
                         ->listTableIndexes($table);
        return array_key_exists($name, $indexes);
    }
};