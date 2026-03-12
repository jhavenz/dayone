<?php

declare(strict_types=1);

use DayOne\Admin\Resources\SubscriptionResource;
use DayOne\Admin\Resources\SubscriptionResource\Pages\EditSubscription;
use DayOne\Admin\Resources\SubscriptionResource\Pages\ListSubscriptions;
use DayOne\Models\Subscription;

it('uses the correct model', function (): void {
    expect(SubscriptionResource::getModel())->toBe(Subscription::class);
});

it('has correct pages without create', function (): void {
    $pages = SubscriptionResource::getPages();

    expect($pages)
        ->toHaveKeys(['index', 'edit'])
        ->not->toHaveKey('create')
        ->and($pages['index']->getPage())->toBe(ListSubscriptions::class)
        ->and($pages['edit']->getPage())->toBe(EditSubscription::class);
});
