<?php

declare(strict_types=1);

use DayOne\Concerns\BelongsToProduct;
use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Models\Product;
use DayOne\Runtime\ProductContextInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::create('test_items', function (Blueprint $table): void {
        $table->ulid('id')->primary();
        $table->foreignUlid('product_id')->constrained('dayone_products');
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('test_items');
});

it('adds product relationship', function (): void {
    $model = new class extends Model {
        use BelongsToProduct;
        use \Illuminate\Database\Eloquent\Concerns\HasUlids;

        protected $table = 'test_items';

        protected $fillable = ['name'];
    };

    expect($model->product())->toBeInstanceOf(BelongsTo::class);
});

it('scopes queries by current product context', function (): void {
    $productA = Product::create(['name' => 'Product A', 'slug' => 'product-a']);
    $productB = Product::create(['name' => 'Product B', 'slug' => 'product-b']);

    $modelClass = new class extends Model {
        use BelongsToProduct;
        use \Illuminate\Database\Eloquent\Concerns\HasUlids;

        protected $table = 'test_items';

        protected $fillable = ['name', 'product_id'];
    };

    $modelClass::create(['name' => 'Item A', 'product_id' => $productA->id]);
    $modelClass::create(['name' => 'Item B', 'product_id' => $productB->id]);

    $context = app(ProductContextInstance::class);
    $context->setProduct($productA);

    $items = $modelClass::all();

    expect($items)->toHaveCount(1)
        ->and($items->first()->name)->toBe('Item A');
});
