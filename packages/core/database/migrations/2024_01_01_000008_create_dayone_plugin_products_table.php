<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dayone_plugin_products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('plugin_id')->constrained('dayone_plugins')->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained('dayone_products')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['plugin_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dayone_plugin_products');
    }
};
