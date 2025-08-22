<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('name');
                $table->string('position')->nullable();
                $table->string('email')->nullable()->index();
                $table->string('phone')->nullable();
                $table->date('hire_date')->nullable();
                $table->decimal('salary', 10, 2)->nullable();
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->date('birth_date')->nullable();
                $table->decimal('height', 5, 2)->nullable();
                $table->decimal('weight', 5, 2)->nullable();
                $table->string('gender', 10)->nullable();
                $table->integer('experience_years')->nullable();
                $table->integer('seniority_years')->nullable();
                $table->string('education')->nullable();
                $table->string('employment_type')->nullable();
                $table->string('current_schedule')->nullable();
                $table->string('avatar_path')->nullable();
                $table->string('invitation_token')->nullable()->unique();
                $table->timestamp('invitation_sent_at')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
