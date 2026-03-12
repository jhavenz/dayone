<?php

declare(strict_types=1);

use DayOne\Models\Product;
use DayOne\Runtime\DefaultProductResolver;
use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->resolver = new DefaultProductResolver();
});

it('resolves from route parameter by slug', function (): void {
    $product = Product::create([
        'name' => 'Route Product',
        'slug' => 'route-product',
    ]);

    $request = Request::create('/test/route-product');
    $route = new \Illuminate\Routing\Route('GET', '/test/{product}', fn () => null);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    $resolved = $this->resolver->resolve($request);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($product->id);
});

it('resolves from X-Product header', function (): void {
    $product = Product::create([
        'name' => 'Header Product',
        'slug' => 'header-product',
    ]);

    $request = Request::create('/test', 'GET', [], [], [], [
        'HTTP_X_PRODUCT' => 'header-product',
    ]);
    $request->setRouteResolver(fn () => null);

    $resolved = $this->resolver->resolve($request);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($product->id);
});

it('resolves from domain', function (): void {
    $product = Product::create([
        'name' => 'Domain Product',
        'slug' => 'domain-product',
        'domain' => 'custom.example.com',
    ]);

    $request = Request::create('https://custom.example.com/test');
    $request->setRouteResolver(fn () => null);

    $resolved = $this->resolver->resolve($request);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($product->id);
});

it('resolves from path prefix', function (): void {
    $product = Product::create([
        'name' => 'Path Product',
        'slug' => 'path-product',
        'path_prefix' => 'app',
    ]);

    $request = Request::create('/app/dashboard');
    $request->setRouteResolver(fn () => null);

    $resolved = $this->resolver->resolve($request);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($product->id);
});

it('returns null when no strategy matches', function (): void {
    $request = Request::create('/nothing');
    $request->setRouteResolver(fn () => null);

    $resolved = $this->resolver->resolve($request);

    expect($resolved)->toBeNull();
});
