<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('archived_staff')) {
            Schema::create('archived_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('position')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                // Campos de ciclo laboral
                $table->date('hire_date')->nullable();
                $table->date('termination_date')->nullable();
                $table->string('termination_reason')->nullable();
                $table->decimal('last_salary', 10, 2)->nullable();
                $table->string('status')->nullable();
                $table->text('notes')->nullable();
                // Metadatos de archivado
                $table->json('original_data')->nullable();
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('archive_reason')->nullable();
                $table->timestamp('archived_at')->useCurrent();
                $table->timestamps();

                $table->index(['business_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_staff');
    }
};
