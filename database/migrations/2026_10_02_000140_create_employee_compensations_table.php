<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->decimal('base_salary', 15, 2);
            $table->char('currency', 3)->default('IDR');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'effective_from']);
            $table->index(['employee_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
