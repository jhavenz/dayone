<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dayone_ejections', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained('dayone_products')->cascadeOnDelete();
            $table->string('concern');
            $table->timestamp('ejected_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'concern']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dayone_ejections');
    }
};
