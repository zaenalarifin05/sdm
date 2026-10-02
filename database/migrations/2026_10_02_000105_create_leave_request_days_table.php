<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_request_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_schedule_id')->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->timestamps();

            $table->unique(['leave_request_id', 'shift_schedule_id']);
            $table->index(['shift_schedule_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_days');
    }
};
