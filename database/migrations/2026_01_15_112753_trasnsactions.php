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
            $table->foreignUuid('wallet_id')->references('id')->on('wallets')->onSoftDelete('cascade');
            $table->integer('amount');
            $table->enum('type', ['deposit', 'withdrawal', 'transfer']);
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->enum('status', ['processing', 'pending', 'completed', 'failed'])->default('processing');
            $table->softDeletes();
            $table->timestamps();
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
