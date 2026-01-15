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
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->char('currency', 3); // TRY, USD, EUR
            // Decimal(19, 4) allows for ample precision for financial calculations
            $table->decimal('balance', 19, 4)->default(0); 
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->string('blocked_reason')->nullable();
            $table->string('unblocked_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // A user should have only one wallet per currency
            $table->unique(['user_id', 'currency']);
            // Index for faster queries
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
