### Day One API

_(for Laravel)_

The multi-saas product platform that brings together all the productivity of the Laravel ecosystem and puts it in your gas tank, then allows AI to be the engine that drives your ability to ship, on _day one!_

To ellaborate a bit:
If you're like me, you've been using a Supabase backend to quickly build out infrastructure for many different frontends.

But then the thought itches at the back of your mind: 
"Laravel has an entire ecosystem of these services, libraries, and tools - isn't there a way to just run a command and be able to sell another product with existing Laravel infrastructure?'"

We've got Cashier, Laravel Cloud, Filament/Nova, Jetstream, Pennant, Inertia/[Resonance](https://github.com/jhavenz/resonance-demo) (shameless plug), Socialite, Reverb, Valet, etc., is there not a simple way to leverage this extensive foundation with the power of one Artisan command? 

I wanted to put this out there and see if it got any traction. With some focused effort, it's worth the nights and weekends I'd be building it with.

What say you, fellow Artisans?

---

## Digging Deeper...sorta

_This is still POC, I'm abbreviating things..._

DayOne is a Composer package (`dayone/core`) that turns a single Laravel 12 app into a multi-product platform. You install it, run `dayone:install`, and then create products with artisan commands. Each product gets its own auth tokens, billing subscriptions, admin panel, API routes, and event hooks -- all isolated from each other but running on the same infrastructure.

The core concept is **product context**. Every authenticated API request resolves which product it belongs to, and everything downstream -- models, middleware, events -- scopes itself automatically. You don't write multi-tenancy logic. You write normal Laravel code and DayOne handles the boundaries.

The project is a Turborepo monorepo. `apps/api` is the Laravel 12 host application. `packages/core` is the `dayone/core` Composer package, symlinked via a path repository so changes are reflected immediately. There's also a TypeScript API client package (`@dayone/client`) generated from the OpenAPI spec.

## What You Get Out of the Box

Per product, with zero configuration:

- **Auth** -- Sanctum tokens scoped to products. Register, login, logout per product.
- **Billing** -- Stripe subscriptions via contract-adapter pattern. Subscribe, cancel, resume, pause. Webhook handling included.
- **Admin** -- Filament admin panels at a configurable path.
- **API routes** -- Product-scoped route group with context middleware already applied.
- **OpenAPI docs** -- Scramble auto-generates specs at `/api/docs`.
- **Events** -- Subscription lifecycle events (created, canceled, expired, resumed, paused, trial-ending) with product-scoped action dispatch.
- **Plugins** -- Per-product plugin system. Create, install, remove via artisan.

## Product Context

DayOne is product-scoped, not tenant-scoped. The `ProductContext` is a scoped singleton resolved per-request by the `ResolveProductContext` middleware (aliased `dayone.product`).

Four resolution strategies, configured in `config/dayone.php`:

1. **Route parameter** -- `/{product}/subscribe`
2. **Header** -- `X-Product` header
3. **Domain** -- product bound to a domain
4. **Path prefix** -- product bound to a URL prefix

Models that use the `BelongsToProduct` trait get automatic query scoping via `ProductScope`. You never manually filter by product -- every query, every relationship, every event is scoped for you.

## Ejection

Every shared concern (billing, auth, admin) can be ejected per product. When a product outgrows the default adapter, eject it:

```bash
php artisan dayone:eject acme billing
```

The product now owns its billing implementation. All other products keep using the shared adapter. The `GuardsEjection` trait checks ejection state before any adapter call, so the transition is seamless.

Changed your mind? Adopt it back:

```bash
php artisan dayone:adopt acme billing
```

## Quick Start

```bash
composer require dayone/core
php artisan dayone:install
php artisan dayone:product:create "Acme App" --domain=acme.example.com
```

Routes are ready immediately:

```
POST   /auth/register
POST   /auth/login
POST   /{product}/subscribe
GET    /{product}/subscription
POST   /{product}/subscription/cancel
POST   /{product}/access/grant
GET    /{product}/access/check
```

Check the health of your setup:

```bash
php artisan dayone:doctor
php artisan dayone:status
php artisan dayone:product:list
```

## Artisan Commands

```
dayone:install                          # First-time setup
dayone:product:create {name}            # Create a new product
dayone:product:list                     # List all products
dayone:product:hibernate {slug}         # Deactivate a product
dayone:product:wake {slug}              # Reactivate a product
dayone:product:archive {slug}           # Archive a product
dayone:eject {slug} {concern}           # Eject billing/auth/admin
dayone:adopt {slug} {concern}           # Reverse an ejection
dayone:plugin:create {name}             # Scaffold a new plugin
dayone:plugin:install {class}           # Install a plugin
dayone:plugin:remove {name}             # Remove a plugin
dayone:plugin:list                      # List installed plugins
dayone:billing:sync                     # Sync billing state with Stripe
dayone:billing:report                   # Generate billing report
dayone:doctor                           # Health check
dayone:status                           # Platform status overview
dayone:contracts:check                  # Verify contract bindings
dayone:types:generate                   # Generate TypeScript types
dayone:migrate                          # Run DayOne migrations
```

## Project Status

This is in active development. What's working:

- Product CRUD and lifecycle (create, hibernate, wake, archive)
- Product context resolution across all four strategies
- Auth flow with product-scoped Sanctum tokens
- Subscription management with Stripe adapter
- Product access control (grant, revoke, check)
- Ejection/adoption system
- Plugin system
- Event architecture with product-scoped actions
- OpenAPI generation
- Full integration test suite

What's next: more billing providers, product-scoped feature flags, CLI dashboard, and documentation site.
