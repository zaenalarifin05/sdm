<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 24)->index();
            $table->string('action', 24)->index();
            $table->foreignId('approver_user_id')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestampTz('acted_at');
            $table->timestamps();

            $table->unique(['leave_request_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
    }
};
