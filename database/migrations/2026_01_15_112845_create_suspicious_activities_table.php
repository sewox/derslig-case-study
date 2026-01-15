<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suspicious_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('transaction_id')->nullable()->constrained('transactions');
            
            $table->string('rule_type'); // e.g., 'velocity_check', 'daily_limit', 'night_transaction'
            $table->integer('risk_score')->default(0);
            
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['pending', 'investigating', 'resolved', 'false_positive'])->default('pending');
            
            $table->text('details')->nullable(); // JSON or text details about why it triggered
            $table->text('admin_note')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspicious_activities');
    }
};
