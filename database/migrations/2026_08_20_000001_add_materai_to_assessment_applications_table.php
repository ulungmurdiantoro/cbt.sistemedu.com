<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Materai elektronik untuk FR.AK.01 — khusus dibubuhkan pada bagian
    // tanda tangan peserta (asesi), dibayar sendiri oleh peserta lewat
    // Midtrans sebelum dibubuhkan lewat API Peruri e-Meterai.
    public function up(): void
    {
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->enum('materai_status', ['none', 'pending_payment', 'paid', 'stamped', 'failed'])
                ->default('none')->after('pakta_signed_at');
            $table->string('materai_order_id')->nullable()->after('materai_status');
            $table->decimal('materai_amount', 10, 2)->nullable()->after('materai_order_id');
            $table->timestamp('materai_paid_at')->nullable()->after('materai_amount');
            $table->timestamp('materai_stamped_at')->nullable()->after('materai_paid_at');
            $table->string('materai_document_path')->nullable()->after('materai_stamped_at');
            $table->text('materai_failure_reason')->nullable()->after('materai_document_path');

            $table->unique('materai_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->dropUnique(['materai_order_id']);
            $table->dropColumn([
                'materai_status',
                'materai_order_id',
                'materai_amount',
                'materai_paid_at',
                'materai_stamped_at',
                'materai_document_path',
                'materai_failure_reason',
            ]);
        });
    }
};
