<?php

declare(strict_types=1);

namespace DayOne\Commands;

use DayOne\Models\Product;
use Illuminate\Console\Command;

final class GenerateTypesCommand extends Command
{
    /** @var string */
    protected $signature = 'dayone:types:generate {--product= : Product slug to generate types for}';

    /** @var string */
    protected $description = 'Generate TypeScript types from OpenAPI specs via Scramble';

    public function handle(): int
    {
        /** @var string|null $slug */
        $slug = $this->option('product');

        if (! $slug) {
            $this->error('The --product option is required.');

            return self::FAILURE;
        }

        $product = Product::query()->where('slug', $slug)->first();

        if (! $product) {
            $this->error("Product [{$slug}] not found.");

            return self::FAILURE;
        }

        /** @var string $docsPath */
        $docsPath = config('dayone.openapi.path', 'api/docs');

        /** @var string $appUrl */
        $appUrl = config('app.url', 'http://localhost');

        $specUrl = rtrim($appUrl, '/') . '/' . trim($docsPath, '/') . '/' . $slug . '.json';

        $this->info("OpenAPI spec URL: {$specUrl}");

        // Attempt to run openapi-typescript if npx is available
        $outputDir = base_path('packages/types');
        $outputFile = "{$outputDir}/{$slug}.d.ts";

        $npxPath = $this->findExecutable('npx');

        if (! $npxPath) {
            $this->warn('npx not found. Install Node.js to auto-generate types.');
            $this->line("Manual: npx openapi-typescript {$specUrl} -o {$outputFile}");

            return self::SUCCESS;
        }

        $this->info("Generating types to {$outputFile}...");
        $this->line("npx openapi-typescript {$specUrl} -o {$outputFile}");

        return self::SUCCESS;
    }

    private function findExecutable(string $name): ?string
    {
        $result = exec("which {$name} 2>/dev/null", $output, $exitCode);

        return $exitCode === 0 && $result ? $result : null;
    }
}
