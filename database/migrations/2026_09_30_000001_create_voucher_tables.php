<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->unsignedInteger('initial_value_pence');
            $table->string('purchaser_name', 120);
            $table->string('purchaser_email', 190)->nullable();
            $table->string('recipient_name', 120)->nullable();
            $table->string('recipient_email', 190)->nullable();
            $table->string('message', 500)->nullable();
            $table->date('expires_on');
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // issue | redeem | void_adjustment
            $table->integer('amount_pence'); // signed: issue +, redeem/void_adjustment -
            $table->string('staff_name', 60);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['voucher_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_transactions');
        Schema::dropIfExists('vouchers');
    }
};
