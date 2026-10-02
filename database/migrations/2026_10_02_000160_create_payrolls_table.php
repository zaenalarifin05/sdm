<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_compensation_id')->constrained()->restrictOnDelete();
            $table->decimal('base_salary_snapshot', 15, 2);
            $table->char('currency', 3)->default('IDR');

            $table->unsignedSmallInteger('scheduled_days')->default(0);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('late_days')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->unsignedSmallInteger('leave_days')->default(0);
            $table->unsignedSmallInteger('permit_days')->default(0);
            $table->unsignedSmallInteger('incomplete_days')->default(0);
            $table->unsignedInteger('approved_overtime_minutes')->default(0);

            $table->string('status', 24)->default('DRAFT')->index();
            $table->timestampTz('generated_at');
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
