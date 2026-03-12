<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Events\ProductCreated;
use DayOne\Models\Product;
use DayOne\Scaffolding\ProductScaffolder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ProductCreateCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:product:create
        {name : The product name}
        {--domain= : The product domain}
        {--path-prefix= : The product path prefix}
        {--no-scaffold : Skip generating the product module directory}
        {--path=products : Base path for the product module directory}';

    /** @var string */
    protected $description = 'Create a new DayOne product';

    public function handle(): int
    {
        /** @var string $name */
        $name = $this->argument('name');
        $slug = Str::slug($name);

        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'domain' => $this->option('domain'),
            'path_prefix' => $this->option('path-prefix'),
            'is_active' => true,
        ]);

        event(new ProductCreated($product->slug, $product->name));

        $this->info("Product created:");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $product->id],
                ['Name', $product->name],
                ['Slug', $product->slug],
                ['Domain', $product->domain ?? '(none)'],
                ['Path Prefix', $product->path_prefix ?? '(none)'],
                ['Active', 'Yes'],
            ],
        );

        if ($this->option('no-scaffold')) {
            return self::SUCCESS;
        }

        /** @var string $basePath */
        $basePath = $this->option('path');
        $absolutePath = str_starts_with($basePath, '/') ? $basePath : base_path($basePath);

        $scaffolder = new ProductScaffolder($name, $slug, $absolutePath);
        $scaffolder->scaffold();

        $this->newLine();
        $this->info("Product module scaffolded at: {$basePath}/{$slug}/");
        $this->line("Generated files:");

        foreach ($scaffolder->generatedPaths() as $path) {
            $relativePath = str_replace(base_path() . '/', '', $path);
            $this->line("  {$relativePath}");
        }

        return self::SUCCESS;
    }
}
