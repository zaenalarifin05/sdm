<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('shift_schedule_id')->constrained()->restrictOnDelete();
            $table->text('reason');
            $table->string('status', 32)->default('PENDING_MANAGER')->index();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('approval_note')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('out_at')->nullable();
            $table->timestampTz('returned_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestampsTz();

            $table->index(['employee_id', 'shift_schedule_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_permissions');
    }
};
