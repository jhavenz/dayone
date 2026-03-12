<?php

declare(strict_types=1);

use DayOne\Scaffolding\ProductScaffolder;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/dayone-scaffolder-' . uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function (): void {
    exec('rm -rf ' . escapeshellarg($this->tempDir));
});

it('creates all expected directories', function (): void {
    $scaffolder = new ProductScaffolder('My Product', 'my-product', $this->tempDir);
    $scaffolder->scaffold();

    $root = $this->tempDir . '/my-product';

    expect(is_dir($root . '/config'))->toBeTrue();
    expect(is_dir($root . '/routes'))->toBeTrue();
    expect(is_dir($root . '/src/Controllers'))->toBeTrue();
    expect(is_dir($root . '/src/Models'))->toBeTrue();
    expect(is_dir($root . '/src/Actions'))->toBeTrue();
    expect(is_dir($root . '/src/Listeners'))->toBeTrue();
    expect(is_dir($root . '/src/Policies'))->toBeTrue();
    expect(is_dir($root . '/database/migrations'))->toBeTrue();
    expect(is_dir($root . '/Filament/Resources'))->toBeTrue();
    expect(is_dir($root . '/Filament/Widgets'))->toBeTrue();
    expect(is_dir($root . '/tests'))->toBeTrue();
});

it('creates all expected files', function (): void {
    $scaffolder = new ProductScaffolder('My Product', 'my-product', $this->tempDir);
    $scaffolder->scaffold();

    $root = $this->tempDir . '/my-product';

    expect(file_exists($root . '/config/product.php'))->toBeTrue();
    expect(file_exists($root . '/routes/api.php'))->toBeTrue();
    expect(file_exists($root . '/routes/web.php'))->toBeTrue();
    expect(file_exists($root . '/ProductServiceProvider.php'))->toBeTrue();

    expect(file_exists($root . '/src/Controllers/.gitkeep'))->toBeTrue();
    expect(file_exists($root . '/src/Models/.gitkeep'))->toBeTrue();
    expect(file_exists($root . '/src/Actions/.gitkeep'))->toBeTrue();
    expect(file_exists($root . '/src/Listeners/.gitkeep'))->toBeTrue();
    expect(file_exists($root . '/src/Policies/.gitkeep'))->toBeTrue();
    expect(file_exists($root . '/database/migrations/.gitkeep'))->toBeTrue();
    expect(file_exists($root . '/Filament/Resources/.gitkeep'))->toBeTrue();
    expect(file_exists($root . '/Filament/Widgets/.gitkeep'))->toBeTrue();
    expect(file_exists($root . '/tests/.gitkeep'))->toBeTrue();
});

it('files contain correct substitutions', function (): void {
    $scaffolder = new ProductScaffolder('My Product', 'my-product', $this->tempDir);
    $scaffolder->scaffold();

    $root = $this->tempDir . '/my-product';

    $config = file_get_contents($root . '/config/product.php');
    expect($config)
        ->toContain("'name' => 'My Product'")
        ->toContain("'slug' => 'my-product'")
        ->toContain('declare(strict_types=1)');

    $provider = file_get_contents($root . '/ProductServiceProvider.php');
    expect($provider)
        ->toContain('namespace Products\\MyProduct;')
        ->toContain('extends BaseProductServiceProvider')
        ->toContain("return 'my-product';")
        ->toContain("return 'My Product';")
        ->toContain('declare(strict_types=1)');

    $apiRoutes = file_get_contents($root . '/routes/api.php');
    expect($apiRoutes)
        ->toContain("prefix('my-product')")
        ->toContain('declare(strict_types=1)');

    $webRoutes = file_get_contents($root . '/routes/web.php');
    expect($webRoutes)
        ->toContain("prefix('my-product')")
        ->toContain('declare(strict_types=1)');
});

it('works with slugs containing hyphens', function (): void {
    $scaffolder = new ProductScaffolder('Super Cool App', 'super-cool-app', $this->tempDir);
    $scaffolder->scaffold();

    $root = $this->tempDir . '/super-cool-app';

    $provider = file_get_contents($root . '/ProductServiceProvider.php');
    expect($provider)
        ->toContain('namespace Products\\SuperCoolApp;')
        ->toContain("return 'super-cool-app';")
        ->toContain("return 'Super Cool App';");

    $config = file_get_contents($root . '/config/product.php');
    expect($config)
        ->toContain("'name' => 'Super Cool App'")
        ->toContain("'slug' => 'super-cool-app'");
});

it('returns generated file paths', function (): void {
    $scaffolder = new ProductScaffolder('My Product', 'my-product', $this->tempDir);

    $paths = $scaffolder->generatedPaths();

    expect($paths)->toBe([
        $this->tempDir . '/my-product/config/product.php',
        $this->tempDir . '/my-product/routes/api.php',
        $this->tempDir . '/my-product/routes/web.php',
        $this->tempDir . '/my-product/ProductServiceProvider.php',
    ]);
});

it('returns product path', function (): void {
    $scaffolder = new ProductScaffolder('My Product', 'my-product', $this->tempDir);

    expect($scaffolder->productPath())->toBe($this->tempDir . '/my-product');
});
