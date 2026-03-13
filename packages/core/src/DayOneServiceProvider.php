<?php

declare(strict_types=1);

namespace DayOne;

use DayOne\Adapters\Admin\FilamentAdminAdapter;
use DayOne\Commands\AdoptCommand;
use DayOne\Commands\BillingReportCommand;
use DayOne\Commands\BillingSyncCommand;
use DayOne\Commands\ContractsCheckCommand;
use DayOne\Commands\DoctorCommand;
use DayOne\Commands\EjectCommand;
use DayOne\Commands\GenerateTypesCommand;
use DayOne\Commands\InstallCommand;
use DayOne\Commands\MigrateCommand;
use DayOne\Commands\PluginCreateCommand;
use DayOne\Commands\PluginInstallCommand;
use DayOne\Commands\PluginListCommand;
use DayOne\Commands\PluginRemoveCommand;
use DayOne\Commands\ProductArchiveCommand;
use DayOne\Commands\ProductCreateCommand;
use DayOne\Commands\ProductHibernateCommand;
use DayOne\Commands\ProductListCommand;
use DayOne\Commands\ProductWakeCommand;
use DayOne\Commands\StatusCommand;
use DayOne\Adapters\Auth\SanctumAuthAdapter;
use DayOne\Adapters\Billing\StripeBillingAdapter;
use DayOne\Adapters\Billing\StripeWebhookHandler;
use DayOne\Ejection\EjectionManager;
use DayOne\Contracts\Admin\V1\AdminManager;
use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\Contracts\Billing\V1\BillingManager;
use DayOne\Contracts\Billing\V1\WebhookHandler;
use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\Contracts\Context\V1\ProductResolver;
use DayOne\Contracts\Events\V1\EventManager;
use DayOne\Events\PaymentFailed;
use DayOne\Events\PaymentSucceeded;
use DayOne\Events\RefundIssued;
use DayOne\Events\SubscriptionCanceled;
use DayOne\Events\SubscriptionCreated;
use DayOne\Events\SubscriptionExpired;
use DayOne\Events\SubscriptionPaused;
use DayOne\Events\SubscriptionResumed;
use DayOne\Events\SubscriptionTrialEnding;
use DayOne\Events\SubscriptionUpdated;
use DayOne\Http\Middleware\RequireProductRole;
use DayOne\Http\Middleware\ResolveProductContext;
use DayOne\Http\OpenApi\DayOneOpenApiExtension;
use Dedoc\Scramble\Scramble;
use DayOne\Listeners\InvokeProductActions;
use DayOne\Plugins\PluginManager;
use DayOne\Runtime\DayOneEventManager;
use DayOne\Runtime\DefaultProductResolver;
use DayOne\Runtime\ProductContextInstance;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class DayOneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dayone.php', 'dayone');

        $this->app->scoped(ProductContextInstance::class);
        $this->app->scoped(ProductContext::class, ProductContextInstance::class);
        $this->app->bind(ProductResolver::class, DefaultProductResolver::class);
        $this->app->bind(AuthManager::class, SanctumAuthAdapter::class);
        $this->app->bind(BillingManager::class, StripeBillingAdapter::class);
        $this->app->bind(WebhookHandler::class, StripeWebhookHandler::class);
        $this->app->singleton(EventManager::class, DayOneEventManager::class);
        $this->app->singleton(AdminManager::class, FilamentAdminAdapter::class);
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(EjectionManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $router = $this->app->make('router');
        $router->aliasMiddleware('dayone.product', ResolveProductContext::class);
        $router->aliasMiddleware('dayone.role', RequireProductRole::class);

        $this->registerWebhookRoute();
        $this->registerEventListeners();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/dayone.php' => config_path('dayone.php'),
            ], 'dayone-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'dayone-migrations');

            $this->commands([
                InstallCommand::class,
                ProductCreateCommand::class,
                ProductListCommand::class,
                ProductHibernateCommand::class,
                ProductWakeCommand::class,
                ProductArchiveCommand::class,
                BillingSyncCommand::class,
                BillingReportCommand::class,
                ContractsCheckCommand::class,
                StatusCommand::class,
                DoctorCommand::class,
                MigrateCommand::class,
                GenerateTypesCommand::class,
                PluginInstallCommand::class,
                PluginListCommand::class,
                PluginCreateCommand::class,
                PluginRemoveCommand::class,
                EjectCommand::class,
                AdoptCommand::class,
            ]);
        }

        $this->bootPlugins();
        $this->registerOpenApi();
        $this->validateConfig();
    }

    private function registerWebhookRoute(): void
    {
        /** @var string $webhookPath */
        $webhookPath = config('dayone.billing.webhook_path', '/webhooks/stripe');

        Route::post($webhookPath, function () {
            /** @var WebhookHandler $handler */
            $handler = app(WebhookHandler::class);

            return $handler->handleWebhook(request());
        })->name('dayone.webhook.stripe');
    }

    private function registerOpenApi(): void
    {
        if (! config('dayone.openapi.enabled', true)) {
            return;
        }

        if (! class_exists(\Dedoc\Scramble\Scramble::class)) {
            return;
        }

        Scramble::registerExtension(DayOneOpenApiExtension::class);

        /** @var string $docsPath */
        $docsPath = config('dayone.openapi.path', 'api/docs');

        Scramble::configure()
            ->expose(
                ui: $docsPath,
                document: $docsPath . '.json',
            );
    }

    private function bootPlugins(): void
    {
        /** @var PluginManager $manager */
        $manager = $this->app->make(PluginManager::class);
        $manager->boot();
    }

    private function validateConfig(): void
    {
        if ($this->app->isProduction()) {
            return;
        }

        $errors = (new \DayOne\Support\ConfigValidator())->validate();

        if ($errors !== []) {
            logger()->warning('DayOne config issues: ' . implode('; ', $errors));
        }
    }

    private function registerEventListeners(): void
    {
        $listener = InvokeProductActions::class;

        Event::listen(SubscriptionCreated::class, $listener);
        Event::listen(SubscriptionUpdated::class, $listener);
        Event::listen(SubscriptionCanceled::class, $listener);
        Event::listen(SubscriptionExpired::class, $listener);
        Event::listen(SubscriptionResumed::class, $listener);
        Event::listen(SubscriptionPaused::class, $listener);
        Event::listen(SubscriptionTrialEnding::class, $listener);
        Event::listen(PaymentSucceeded::class, $listener);
        Event::listen(PaymentFailed::class, $listener);
        Event::listen(RefundIssued::class, $listener);
    }
}
