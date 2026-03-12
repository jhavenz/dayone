<?php

declare(strict_types=1);

use DayOne\Events\ProductCreated;
use DayOne\Models\Product;
use Illuminate\Support\Facades\Event;

it('creates a product with the given name', function (): void {
    Event::fake();

    $this->artisan('dayone:product:create', ['name' => 'My App', '--no-scaffold' => true])
        ->assertSuccessful();

    $product = Product::where('slug', 'my-app')->first();

    expect($product)
        ->not->toBeNull()
        ->name->toBe('My App')
        ->isActive->toBeTrue();
});

it('auto-generates slug from name', function (): void {
    Event::fake();

    $this->artisan('dayone:product:create', ['name' => 'Hello World App', '--no-scaffold' => true])
        ->assertSuccessful();

    expect(Product::where('slug', 'hello-world-app')->exists())->toBeTrue();
});

it('accepts domain and path-prefix options', function (): void {
    Event::fake();

    $this->artisan('dayone:product:create', [
        'name' => 'Domain App',
        '--domain' => 'app.example.com',
        '--path-prefix' => '/app',
        '--no-scaffold' => true,
    ])->assertSuccessful();

    $product = Product::where('slug', 'domain-app')->first();

    expect($product)
        ->domain->toBe('app.example.com')
        ->path_prefix->toBe('/app');
});

it('dispatches ProductCreated event', function (): void {
    Event::fake();

    $this->artisan('dayone:product:create', ['name' => 'Event App', '--no-scaffold' => true])
        ->assertSuccessful();

    Event::assertDispatched(ProductCreated::class, function (ProductCreated $event): bool {
        return $event->productSlug === 'event-app'
            && $event->productName === 'Event App';
    });
});

it('scaffolds product directory structure', function (): void {
    Event::fake();

    $tempDir = sys_get_temp_dir() . '/dayone-test-' . uniqid();
    mkdir($tempDir, 0755, true);

    try {
        $this->artisan('dayone:product:create', [
            'name' => 'Test Product',
            '--path' => $tempDir,
        ])->assertSuccessful();

        $productPath = $tempDir . '/test-product';

        expect(is_dir($productPath))->toBeTrue();
        expect(is_dir($productPath . '/src/Controllers'))->toBeTrue();
        expect(is_dir($productPath . '/src/Models'))->toBeTrue();
        expect(is_dir($productPath . '/src/Actions'))->toBeTrue();
        expect(is_dir($productPath . '/src/Listeners'))->toBeTrue();
        expect(is_dir($productPath . '/src/Policies'))->toBeTrue();
        expect(is_dir($productPath . '/database/migrations'))->toBeTrue();
        expect(is_dir($productPath . '/Filament/Resources'))->toBeTrue();
        expect(is_dir($productPath . '/Filament/Widgets'))->toBeTrue();
        expect(is_dir($productPath . '/tests'))->toBeTrue();
        expect(file_exists($productPath . '/config/product.php'))->toBeTrue();
        expect(file_exists($productPath . '/routes/api.php'))->toBeTrue();
        expect(file_exists($productPath . '/routes/web.php'))->toBeTrue();
        expect(file_exists($productPath . '/ProductServiceProvider.php'))->toBeTrue();
    } finally {
        exec('rm -rf ' . escapeshellarg($tempDir));
    }
});

it('generates ProductServiceProvider with correct namespace', function (): void {
    Event::fake();

    $tempDir = sys_get_temp_dir() . '/dayone-test-' . uniqid();
    mkdir($tempDir, 0755, true);

    try {
        $this->artisan('dayone:product:create', [
            'name' => 'My Product',
            '--path' => $tempDir,
        ])->assertSuccessful();

        $content = file_get_contents($tempDir . '/my-product/ProductServiceProvider.php');

        expect($content)
            ->toContain('namespace Products\\MyProduct;')
            ->toContain('use DayOne\\Support\\ProductServiceProvider as BaseProductServiceProvider;')
            ->toContain("return 'my-product';")
            ->toContain("return 'My Product';");
    } finally {
        exec('rm -rf ' . escapeshellarg($tempDir));
    }
});

it('generates config with correct slug and name', function (): void {
    Event::fake();

    $tempDir = sys_get_temp_dir() . '/dayone-test-' . uniqid();
    mkdir($tempDir, 0755, true);

    try {
        $this->artisan('dayone:product:create', [
            'name' => 'My Product',
            '--path' => $tempDir,
        ])->assertSuccessful();

        $content = file_get_contents($tempDir . '/my-product/config/product.php');

        expect($content)
            ->toContain("'name' => 'My Product'")
            ->toContain("'slug' => 'my-product'");
    } finally {
        exec('rm -rf ' . escapeshellarg($tempDir));
    }
});

it('skips scaffolding with --no-scaffold option', function (): void {
    Event::fake();

    $tempDir = sys_get_temp_dir() . '/dayone-test-' . uniqid();
    mkdir($tempDir, 0755, true);

    try {
        $this->artisan('dayone:product:create', [
            'name' => 'Skip Product',
            '--no-scaffold' => true,
            '--path' => $tempDir,
        ])->assertSuccessful();

        expect(is_dir($tempDir . '/skip-product'))->toBeFalse();
        expect(Product::where('slug', 'skip-product')->exists())->toBeTrue();
    } finally {
        exec('rm -rf ' . escapeshellarg($tempDir));
    }
});

it('generates routes with correct prefix', function (): void {
    Event::fake();

    $tempDir = sys_get_temp_dir() . '/dayone-test-' . uniqid();
    mkdir($tempDir, 0755, true);

    try {
        $this->artisan('dayone:product:create', [
            'name' => 'Route Product',
            '--path' => $tempDir,
        ])->assertSuccessful();

        $apiRoutes = file_get_contents($tempDir . '/route-product/routes/api.php');
        $webRoutes = file_get_contents($tempDir . '/route-product/routes/web.php');

        expect($apiRoutes)
            ->toContain("prefix('route-product')")
            ->toContain("'api', 'dayone.product'");

        expect($webRoutes)
            ->toContain("prefix('route-product')")
            ->toContain("'web', 'dayone.product'");
    } finally {
        exec('rm -rf ' . escapeshellarg($tempDir));
    }
});
