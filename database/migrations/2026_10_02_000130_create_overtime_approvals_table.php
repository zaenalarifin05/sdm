<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 24)->index();
            $table->string('action', 24)->index();
            $table->foreignId('approver_user_id')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestampTz('acted_at');
            $table->timestampsTz();

            $table->unique(['overtime_request_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_approvals');
    }
};
