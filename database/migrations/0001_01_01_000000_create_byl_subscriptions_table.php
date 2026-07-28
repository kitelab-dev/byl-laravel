<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->unsignedBigInteger('byl_id')->unique();
            $table->unsignedBigInteger('byl_customer_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('price_id')->nullable();
            $table->string('lookup_key')->nullable();
            $table->string('status');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestamps();

            $table->index(['billable_type', 'billable_id', 'status']);
            $table->index('lookup_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return config('byl.billable.subscriptions_table', 'byl_subscriptions');
    }
};
