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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reference_id')->nullable()->index(); // For Idempotency or external ref
            
            $table->enum('type', ['deposit', 'withdraw', 'transfer', 'refund']);
            
            // source_wallet_id is null for Deposit (money comes from outside)
            // target_wallet_id is null for Withdraw (money goes outside)
            // For Transfer: Source -> Target
            $table->foreignUuid('source_wallet_id')->nullable()->constrained('wallets');
            $table->foreignUuid('target_wallet_id')->nullable()->constrained('wallets');
            
            // Amounts
            $table->decimal('amount', 19, 4);
            $table->decimal('fee', 19, 4)->default(0); // Fee charged on this transaction
            $table->char('currency', 3);
            
            $table->enum('status', ['pending', 'completed', 'failed', 'pending_review', 'rejected'])->default('pending')->index();
            
            $table->string('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable(); // Extra details like fraud scores, refund reason etc.
            
            $table->uuid('related_transaction_id')->nullable(); // For Refunds, pointing to original transaction
            
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for Reports
            $table->index(['source_wallet_id', 'created_at']);
            $table->index(['target_wallet_id', 'created_at']);
            $table->index(['type', 'status']);
            $table->index('created_at'); // For date range filtering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
