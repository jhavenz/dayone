<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\Models\PlanFeature;
use DayOne\Models\Product;
use Illuminate\Database\Seeder;

class DayOneSeeder extends Seeder
{
    public function run(): void
    {
        $acme = Product::create([
            'name' => 'Acme',
            'slug' => 'acme',
            'is_active' => true,
            'settings' => ['theme' => 'blue'],
        ]);

        $beta = Product::create([
            'name' => 'Beta',
            'slug' => 'beta',
            'is_active' => true,
            'settings' => ['theme' => 'green'],
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@dayone.test',
        ]);

        $member = User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@dayone.test',
        ]);

        $auth = app(AuthManager::class);
        $auth->grantProductAccess($admin, $acme, 'admin');
        $auth->grantProductAccess($admin, $beta, 'admin');
        $auth->grantProductAccess($member, $acme, 'user');

        $features = [
            ['product_id' => $acme->id, 'plan_id' => 'acme_starter', 'plan_name' => 'Starter', 'feature_key' => 'seats', 'feature_value' => '5', 'sort_order' => 0],
            ['product_id' => $acme->id, 'plan_id' => 'acme_starter', 'plan_name' => 'Starter', 'feature_key' => 'storage_gb', 'feature_value' => '10', 'sort_order' => 1],
            ['product_id' => $acme->id, 'plan_id' => 'acme_pro', 'plan_name' => 'Pro', 'feature_key' => 'seats', 'feature_value' => 'unlimited', 'sort_order' => 2],
            ['product_id' => $acme->id, 'plan_id' => 'acme_pro', 'plan_name' => 'Pro', 'feature_key' => 'storage_gb', 'feature_value' => '100', 'sort_order' => 3],
            ['product_id' => $beta->id, 'plan_id' => 'beta_free', 'plan_name' => 'Free', 'feature_key' => 'api_calls', 'feature_value' => '1000', 'sort_order' => 0],
        ];

        foreach ($features as $feature) {
            PlanFeature::create($feature);
        }
    }
}
