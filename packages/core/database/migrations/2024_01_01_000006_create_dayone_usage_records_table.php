<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dayone_usage_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('subscription_id')->constrained('dayone_subscriptions')->cascadeOnDelete();
            $table->string('feature');
            $table->integer('quantity');
            $table->timestamp('recorded_at');
            $table->string('stripe_usage_record_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dayone_usage_records');
    }
};
