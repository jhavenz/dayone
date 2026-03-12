<?php

declare(strict_types=1);

namespace DayOne\Support;

use Illuminate\Support\ServiceProvider;

abstract class ProductServiceProvider extends ServiceProvider
{
    abstract public function productSlug(): string;

    abstract public function productName(): string;

    public function register(): void
    {
        $configPath = $this->configPath();

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'dayone.products.' . $this->productSlug());
        }
    }

    public function boot(): void
    {
        $routePath = $this->routePath();
        $migrationPath = $this->migrationPath();

        if (file_exists($routePath)) {
            $this->loadRoutesFrom($routePath);
        }

        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        $viewPath = dirname($this->configPath()) . '/../resources/views';

        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, $this->productSlug());
        }
    }

    public function configPath(): string
    {
        return $this->basePath() . '/config/' . $this->productSlug() . '.php';
    }

    public function migrationPath(): string
    {
        return $this->basePath() . '/database/migrations';
    }

    public function routePath(): string
    {
        return $this->basePath() . '/routes/api.php';
    }

    protected function basePath(): string
    {
        $reflector = new \ReflectionClass(static::class);

        return dirname((string) $reflector->getFileName(), 2);
    }
}
