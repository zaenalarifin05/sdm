<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('shift_schedule_id')->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->unsignedInteger('duration_minutes');
            $table->text('reason');
            $table->string('status', 32)->default('PENDING_MANAGER')->index();
            $table->timestampsTz();

            $table->index(['employee_id', 'work_date']);
            $table->index(['employee_id', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
