<?php

declare(strict_types=1);

namespace DayOne\Scaffolding;

use Illuminate\Support\Str;

final class ProductScaffolder
{
    private readonly string $studlySlug;

    public function __construct(
        private readonly string $name,
        private readonly string $slug,
        private readonly string $basePath,
    ) {
        $this->studlySlug = Str::studly($this->slug);
    }

    public function scaffold(): void
    {
        $this->createDirectories();
        $this->createConfigFile();
        $this->createApiRoutes();
        $this->createWebRoutes();
        $this->createServiceProvider();
    }

    /** @return list<string> */
    public function generatedPaths(): array
    {
        $root = $this->productPath();

        return [
            $root . '/config/product.php',
            $root . '/routes/api.php',
            $root . '/routes/web.php',
            $root . '/ProductServiceProvider.php',
        ];
    }

    public function productPath(): string
    {
        return $this->basePath . '/' . $this->slug;
    }

    private function createDirectories(): void
    {
        $root = $this->productPath();

        $directories = [
            $root . '/config',
            $root . '/routes',
            $root . '/src/Controllers',
            $root . '/src/Models',
            $root . '/src/Actions',
            $root . '/src/Listeners',
            $root . '/src/Policies',
            $root . '/database/migrations',
            $root . '/Filament/Resources',
            $root . '/Filament/Widgets',
            $root . '/tests',
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }

        $gitkeepDirs = [
            $root . '/src/Controllers',
            $root . '/src/Models',
            $root . '/src/Actions',
            $root . '/src/Listeners',
            $root . '/src/Policies',
            $root . '/database/migrations',
            $root . '/Filament/Resources',
            $root . '/Filament/Widgets',
            $root . '/tests',
        ];

        foreach ($gitkeepDirs as $directory) {
            $gitkeep = $directory . '/.gitkeep';
            if (! file_exists($gitkeep)) {
                file_put_contents($gitkeep, '');
            }
        }
    }

    private function createConfigFile(): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'name' => '{$this->name}',
                'slug' => '{$this->slug}',
                'billing' => [
                    'plans' => [],
                ],
                'actions' => [
                    'on_subscribe' => null,
                    'on_cancel' => null,
                    'on_payment_failed' => null,
                ],
            ];

            PHP;

        file_put_contents(
            $this->productPath() . '/config/product.php',
            $this->dedent($content),
        );
    }

    private function createApiRoutes(): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            use Illuminate\Support\Facades\Route;

            Route::middleware(['api', 'dayone.product'])->prefix('{$this->slug}')->group(function (): void {
            });

            PHP;

        file_put_contents(
            $this->productPath() . '/routes/api.php',
            $this->dedent($content),
        );
    }

    private function createWebRoutes(): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            use Illuminate\Support\Facades\Route;

            Route::middleware(['web', 'dayone.product'])->prefix('{$this->slug}')->group(function (): void {
            });

            PHP;

        file_put_contents(
            $this->productPath() . '/routes/web.php',
            $this->dedent($content),
        );
    }

    private function createServiceProvider(): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Products\\{$this->studlySlug};

            use DayOne\Support\ProductServiceProvider as BaseProductServiceProvider;

            final class ProductServiceProvider extends BaseProductServiceProvider
            {
                public function productSlug(): string
                {
                    return '{$this->slug}';
                }

                public function productName(): string
                {
                    return '{$this->name}';
                }
            }

            PHP;

        file_put_contents(
            $this->productPath() . '/ProductServiceProvider.php',
            $this->dedent($content),
        );
    }

    private function dedent(string $content): string
    {
        /** @var string $result */
        $result = preg_replace('/^            /m', '', $content);

        return $result;
    }
}
