<?php

declare(strict_types=1);

it('reports bound contracts', function (): void {
    $this->artisan('dayone:contracts:check')
        ->expectsOutputToContain('[OK]');
});

it('lists all V1 contracts', function (): void {
    $this->artisan('dayone:contracts:check')
        ->expectsOutputToContain('ProductContext')
        ->expectsOutputToContain('ProductResolver')
        ->expectsOutputToContain('AuthManager')
        ->expectsOutputToContain('BillingManager')
        ->expectsOutputToContain('WebhookHandler')
        ->expectsOutputToContain('EventManager')
        ->expectsOutputToContain('AdminManager');
});
