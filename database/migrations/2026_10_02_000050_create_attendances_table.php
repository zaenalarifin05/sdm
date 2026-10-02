<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('shift_schedule_id')->unique()->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->timestampTz('scheduled_start_at');
            $table->timestampTz('scheduled_end_at');
            $table->timestampTz('check_in_at')->nullable();
            $table->timestampTz('check_out_at')->nullable();
            $table->string('state', 24)->default('checked_in')->index();
            $table->string('attendance_status', 24)->nullable()->index();
            $table->unsignedInteger('late_minutes')->nullable();
            $table->unsignedInteger('early_leave_minutes')->nullable();
            $table->timestampsTz();

            $table->index(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
