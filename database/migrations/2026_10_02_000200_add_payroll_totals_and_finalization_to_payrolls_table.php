<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('total_allowance', 15, 2)->default(0)->after('approved_overtime_minutes');
            $table->decimal('gross_pay', 15, 2)->default(0)->after('total_allowance');
            $table->decimal('total_deduction', 15, 2)->default(0)->after('gross_pay');
            $table->decimal('net_pay', 15, 2)->default(0)->after('total_deduction');
            $table->timestampTz('finalized_at')->nullable()->after('generated_by');
            $table->foreignId('finalized_by')->nullable()->after('finalized_at')
                ->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn([
                'total_allowance',
                'gross_pay',
                'total_deduction',
                'net_pay',
                'finalized_at',
            ]);
        });
    }
};
