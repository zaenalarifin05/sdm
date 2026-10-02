<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->string('policy_key', 64)->index();
            $table->boolean('enabled')->default(false);
            $table->string('mode', 64)->nullable();
            $table->string('value', 64)->nullable();
            $table->date('effective_from')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['policy_key', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_policy_versions');
    }
};
