<?php

declare(strict_types=1);

namespace DayOne\Adapters\Auth;

use DayOne\Concerns\GuardsEjection;
use DayOne\Contracts\Auth\V1\AuthManager;
use DayOne\Contracts\Context\V1\ProductContext;
use DayOne\DTOs\TokenResult;
use DayOne\Events\ProductAccessGranted;
use DayOne\Events\ProductAccessRevoked;
use DayOne\Events\TokenIssued;
use DayOne\Events\TokenRevoked;
use DayOne\Models\Product;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;

final class SanctumAuthAdapter implements AuthManager
{
    use GuardsEjection;

    public function __construct(
        private readonly ProductContext $context,
    ) {}

    /** @param array<string> $abilities */
    public function issueToken(Authenticatable $user, string $tokenName, array $abilities = []): TokenResult
    {
        $this->guardEjection('auth');
        $this->assertHasApiTokens($user);

        $productSlug = null;

        if ($this->context->hasProduct()) {
            $productSlug = $this->context->product()?->slug;

            if ($productSlug !== null) {
                $abilities[] = "product:{$productSlug}";
            }
        }

        /** @var Authenticatable&HasApiTokensContract $user */
        $newAccessToken = $user->createToken(name: $tokenName, abilities: $abilities);

        $tokenId = (string) $newAccessToken->accessToken->getKey();

        event(new TokenIssued(
            user: $user,
            tokenId: $tokenId,
            productSlug: $productSlug,
        ));

        return new TokenResult(
            token: $newAccessToken->plainTextToken,
            tokenId: $tokenId,
        );
    }

    public function revokeToken(Authenticatable $user, string $tokenId): void
    {
        $this->guardEjection('auth');
        $this->assertHasApiTokens($user);

        /** @var Authenticatable&HasApiTokensContract $user */
        $user->tokens()->where('id', $tokenId)->delete();

        event(new TokenRevoked(user: $user, tokenId: $tokenId));
    }

    public function revokeAllTokens(Authenticatable $user): void
    {
        $this->guardEjection('auth');
        $this->assertHasApiTokens($user);

        /** @var Authenticatable&HasApiTokensContract $user */
        $user->tokens()->delete();
    }

    public function grantProductAccess(Authenticatable $user, Product $product, string $role = 'user'): void
    {
        $this->guardEjection('auth');

        $exists = DB::table('dayone_user_products')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('product_id', $product->getKey())
            ->exists();

        if ($exists) {
            DB::table('dayone_user_products')
                ->where('user_id', $user->getAuthIdentifier())
                ->where('product_id', $product->getKey())
                ->update(['role' => $role, 'updated_at' => now()]);
        } else {
            DB::table('dayone_user_products')->insert([
                'id' => Str::ulid()->toBase32(),
                'user_id' => $user->getAuthIdentifier(),
                'product_id' => $product->getKey(),
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        event(new ProductAccessGranted(user: $user, product: $product, role: $role));
    }

    public function revokeProductAccess(Authenticatable $user, Product $product): void
    {
        $this->guardEjection('auth');

        DB::table('dayone_user_products')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('product_id', $product->getKey())
            ->delete();

        event(new ProductAccessRevoked(user: $user, product: $product));
    }

    public function hasProductAccess(Authenticatable $user, Product $product): bool
    {
        $this->guardEjection('auth');

        return DB::table('dayone_user_products')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('product_id', $product->getKey())
            ->exists();
    }

    private function assertHasApiTokens(Authenticatable $user): void
    {
        if ($user instanceof HasApiTokensContract) {
            return;
        }

        if (in_array(HasApiTokens::class, class_uses_recursive($user), true)) {
            return;
        }

        throw new \InvalidArgumentException(
            'The user model must use the Laravel\Sanctum\HasApiTokens trait.',
        );
    }
}
