<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dayone_plan_features', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('dayone_products')->cascadeOnDelete();
            $table->string('plan_id');
            $table->string('plan_name');
            $table->string('feature_key');
            $table->string('feature_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dayone_plan_features');
    }
};
