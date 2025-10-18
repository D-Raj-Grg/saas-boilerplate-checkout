# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a multi-stack SaaS boilerplate consisting of two main components:
1. **Laravel Backend API** (`/laravel`) - Multi-tenant API with authentication, Nepal payment gateways, and workspace management
2. **Next.js Frontend** (`/nextjs`) - Modern React-based UI using App Router and shadcn/ui

## Architecture Overview

### Multi-Tenancy Hierarchy
```
User
├── Organizations (top-level tenants)
│   ├── Owner (OrganizationRole: OWNER/ADMIN/MEMBER)
│   ├── Plans & Subscriptions (via SureCart)
│   ├── Workspaces (sub-tenants)
│   │   ├── Members (WorkspaceRole: MANAGER/EDITOR/VIEWER)
│   │   └── Connections (external integrations, e.g., WordPress)
│   └── Invitations
```

**Key Relationships:**
- Organizations own multiple Workspaces
- Users belong to multiple Organizations with different roles
- Users belong to multiple Workspaces within an Organization
- Plans are attached to Organizations, not Users
- Payments track transactions for plan purchases

### Context-Based Routing Pattern (Laravel)
The API uses a user context pattern where users set their current organization/workspace:
- `POST /api/v1/user/current-organization/{uuid}` - Set active organization
- `POST /api/v1/user/current-workspace/{uuid}` - Set active workspace
- Routes prefixed with `context:organization` middleware require an active organization
- Routes prefixed with `context:workspace` middleware require an active workspace
- Context is stored on the `users` table: `current_organization_id` and `current_workspace_id`

### Key Custom Middleware (Laravel)
- `EnsureValidUserContext` - Validates organization/workspace context
- `EnsureUserBelongsToOrganization` - Checks organization membership
- `EnsureUserBelongsToWorkspace` - Checks workspace membership
- `RateLimitHeaders` - Plan-based rate limiting with custom headers
- `ValidateUuidParameter` - Validates UUID route parameters

### Database Schema Patterns
- **UUIDs as Primary Keys**: All models expose UUIDs to the frontend (internal IDs are hidden)
- **Soft Deletes**: Most models support soft deletion for audit trails
- **Pivot Tables with Custom Models**: `OrganizationUser`, `WorkspaceUser`, `OrganizationPlan`
- **Plan System**: Organizations can have multiple plans attached (via `organization_plans` pivot)
  - Plans have priorities (higher number = higher priority)
  - `getCurrentPlan()` returns the highest-priority active plan
  - Plan limits cached for performance (300-600 seconds)

## Development Commands

### Laravel Backend (`/laravel`)
```bash
# Setup
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Development (runs all services in parallel)
composer run dev
# This starts: Laravel server, queue worker, logs (Pail), and Vite

# Individual services
php artisan serve           # Start Laravel dev server (port 8000)
php artisan queue:work      # Process queue jobs
php artisan pail            # Real-time logs

# Testing
php artisan test                    # Run all tests
php artisan test --parallel         # Faster parallel execution
php artisan test --filter=invitation  # Run specific tests
composer test:pest                  # Direct Pest execution

# Code Quality
./vendor/bin/pint              # Format code (Laravel Pint)
./vendor/bin/pint --test       # Check formatting
composer analyze               # Run PHPStan (level 8)
composer analyze:baseline      # Generate PHPStan baseline
composer check                 # Run all checks (format, lint, analyze, test)

# Production Optimization
php artisan optimize          # Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Next.js Frontend (`/nextjs`)
```bash
# Setup
pnpm install
cp .env.example .env.local
# Set NEXT_PUBLIC_API_BASE_URL to your Laravel backend URL

# Development
pnpm dev              # Start dev server with Turbopack (port 3000)
pnpm build            # Production build
pnpm start            # Start production server
pnpm lint             # Run ESLint

# Testing
pnpm test             # Run Jest tests
pnpm test:watch       # Watch mode
pnpm test:coverage    # With coverage

# Environment-specific builds
pnpm build:local
pnpm build:staging
pnpm build:production

# Adding shadcn/ui components
pnpm dlx shadcn@latest add <component-name>
# Example: pnpm dlx shadcn@latest add button
```

### WordPress Connector (`/wordpress-connector`)
```bash
# Setup
composer install
npm install

# Build
npm run build        # Build plugin assets
./build.sh           # Create distribution package
```

### Multi-Currency & Market Support
Plans can target different markets with different currencies:
```php
// Currency utilities
$currencyService = app(CurrencyService::class);

// Format price with currency
$formatted = $currencyService->format(299.0, 'NPR'); // "Rs. 299.00"

// Get available gateways for currency
$gateways = $currencyService->getAvailableGatewaysForCurrency('NPR');
// Returns: ['esewa', 'khalti', 'fonepay', 'mock']

// Check if gateway supports currency
$supported = $currencyService->gatewaySupportsCurrency('esewa', 'USD'); // false

// Get user's preferred currency
$currency = $currencyService->getUserCurrency($user); // Respects user preference
```

**Supported Currencies**: NPR, USD, EUR, GBP, INR
**Currency by Market**:
- Nepal → NPR (eSewa, Khalti, Fonepay)
- International → USD (Stripe)
- India → INR (Stripe)
- Europe → EUR (Stripe)
- UK → GBP (Stripe)

## Key Laravel Patterns

### Plan Management
Plans are managed through the `HasPricingPlan` trait (used by Organization model):
```php
// Attach a plan (handles free plan logic, auto-cancellation)
$organization->attachPlan($plan, $attributes);

// Get current plan (cached, returns highest-priority active plan)
$currentPlan = $organization->getCurrentPlan();

// Check features
if ($organization->hasFeature('api_integration')) { /* ... */ }
$limit = $organization->getLimit('team_members');
if ($organization->canUse('workspaces', 1)) { /* ... */ }

// Rate limiting
$rateLimit = $organization->getRateLimit('api');

// Trial information
$trialInfo = $organization->getTrialInfo();
$organization->isInTrial();
$organization->isTrialExpired();
```

**Important**: Plan cache is cleared automatically in `attachPlan()`. Cache keys use pattern `org_{id}_*`.

### Nepal Payment Gateway Integration
- Supported gateways: **eSewa**, **Khalti**, **Fonepay**, **Mock** (development), **Stripe** (international)
- Payment initiation: `POST /api/v1/payments/initiate`
- Payment verification: `GET /api/v1/payments/{uuid}/verify`
- Payment history: `GET /api/v1/payments/history`
- Gateway services located in: `app/Services/PaymentGateway/`
- Billing orchestration: `app/Services/BillingService.php`
- Currency service: `app/Services/CurrencyService.php`

#### Payment Gateway Callback Patterns
**Critical**: Different gateways handle return URLs differently:
- **eSewa**: Appends `?data=base64_encoded_json` to return URL. Extract `transaction_uuid` from decoded `data` parameter.
- **Khalti**: Appends `&pidx=xxx&...` with `&` separator. Use `payment_uuid` from URL params.
- **Fonepay**: Appends params with `&` separator. Use `payment_uuid` from URL params.

**Implementation Notes**:
- eSewa success/failure URLs should NOT include query params (eSewa uses `?` not `&` when appending)
- Khalti/Fonepay URLs should include `?payment_uuid={uuid}` (they properly append with `&`)
- Frontend success page extracts payment UUID from either URL params or eSewa's `data` parameter

### Organization & Workspace Management
```php
// Organization operations
$organization->addUser($user, OrganizationRole::ADMIN, $invitedBy);
$organization->removeUser($user);
$organization->updateUserRole($user, OrganizationRole::OWNER);
$organization->isOwnedBy($user);
$organization->isUserAdmin($user);

// Workspace operations
$workspace->addUser($user, WorkspaceRole::EDITOR);
$workspace->removeUser($user);
$workspace->changeUserRole($user, WorkspaceRole::MANAGER);
```

### Invitations System
- Invitations expire after 7 days (configurable)
- Token-based acceptance: `POST /api/v1/invitations/{token}/accept`
- Can invite users to specific workspaces with roles
- Email sent via `InvitationMail` mailable

## Next.js Architecture

### App Router Structure
```
app/
├── (guest)/          # Public pages (no auth required)
│   ├── login/
│   ├── signup/
│   ├── pricing/
│   └── ...
├── (auth)/           # Protected pages (auth required)
│   ├── dashboard/
│   ├── organization/
│   ├── workspace/
│   └── settings/
```

### Server Actions Pattern
All API calls are made through Next.js Server Actions in `/actions`:
- `auth.ts` - Login, signup, logout, email verification, password reset
- `organization.ts` - Organization CRUD, member management
- `workspace.ts` - Workspace CRUD, member management
- `invitation.ts` - Send invitations, accept/decline
- `plans.ts` - Fetch plans, get checkout URLs
- `connection.ts` - WordPress connection exchange

**Pattern**: Server Actions use `cookies()` to access auth token and make API requests to Laravel backend.

### Authentication Flow
1. User logs in via Server Action (`actions/auth.ts`)
2. Server Action receives JWT token from Laravel
3. Token stored in HTTP-only cookie (`AUTH_TOKEN_NAME`)
4. Middleware (`middleware.ts`) protects routes
5. Server Actions include token in API requests to Laravel

### State Management
- **Zustand** for client-side state (`/stores`)
- User state managed in `user-store.ts`
- Organization/workspace context synced with backend

## Payment Flow

### Complete User Journey
1. **Pricing Page** (`/pricing`) - User browses plans
2. **Checkout Page** (`/checkout/[planSlug]`) - Select payment gateway (eSewa/Khalti/Fonepay)
3. **Payment Gateway** - User completes payment on external gateway
4. **Success Callback** (`/payment/success`) - Verify payment & attach plan
5. **Dashboard** - User redirected with active subscription

### Payment Verification Flow
1. Gateway redirects user to success page with payment data
2. Frontend extracts `payment_uuid`:
   - **eSewa**: Decode base64 `data` param → extract `transaction_uuid`
   - **Khalti/Fonepay**: Get `payment_uuid` from URL query param
3. Frontend calls verification API: `GET /api/v1/payments/{uuid}/verify?{gateway_params}`
4. Backend verifies with gateway and attaches plan to organization

**Gateway-Specific Parameters**:
- **eSewa v2**: `data` (base64), `transaction_uuid`, `total_amount`
- **Khalti**: `pidx` (payment index), `transaction_id`, `status`
- **Fonepay**: Implementation varies by integration type

**Important**: Payment verification extracts all query params automatically via `VerifyPaymentRequest`

## Important Environment Variables

### Laravel (`/laravel/.env`)
```env
APP_NAME="Your SaaS Name"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

# Database
DB_CONNECTION=mysql
DB_DATABASE=your_database

# Redis (required for queues/cache)
REDIS_HOST=127.0.0.1
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Payment Gateways (Nepal)
PAYMENT_GATEWAY_DEFAULT=esewa
ESEWA_MERCHANT_ID=EPAYTEST
ESEWA_SECRET_KEY=your_secret_key
ESEWA_API_URL=https://rc-epay.esewa.com.np/api/epay/main/v2/form
ESEWA_VERIFY_URL=https://rc.esewa.com.np/api/epay/transaction/status
KHALTI_PUBLIC_KEY=
KHALTI_SECRET_KEY=
KHALTI_API_URL=https://khalti.com/api/v2/
FONEPAY_MERCHANT_CODE=
FONEPAY_SECRET=

# Currency & Markets
DEFAULT_CURRENCY=NPR
CURRENCY_AUTO_DETECT=true

# PostHog (optional)
POSTHOG_API_KEY=
POSTHOG_HOST=https://app.posthog.com
```

### Next.js (`/nextjs/.env.local`)
```env
NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1
AUTH_TOKEN_NAME=bsf-auth-token
NEXT_PUBLIC_APP_NAME="Your SaaS Name"
```

## Testing

### Laravel Tests
- Framework: **Pest PHP**
- 410 tests with 1666 assertions
- Test structure:
  - `tests/Feature/Api/V1/` - API endpoint tests
  - `tests/Unit/` - Unit tests for traits, services
  - `tests/Feature/Mail/` - Email tests

**Writing Tests**: Use Pest's function-based syntax:
```php
use function Pest\Laravel\{postJson, getJson};

test('user can create organization', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = postJson('/api/v1/organization', ['name' => 'Test Org']);

    $response->assertStatus(201);
    expect($response->json('data.name'))->toBe('Test Org');
});
```

### Common Issues & Solutions

**Issue**: eSewa payment returns "405 Method Not Allowed" or malformed URL
- **Solution**: Ensure success/failure URLs in `EsewaGateway` do NOT include `?payment_uuid=`. eSewa appends `?data=` (not `&data=`), causing double query separators. Frontend should extract `transaction_uuid` from the decoded `data` parameter.

**Issue**: Payment verification shows "Resource not found"
- **Solution**: Check that payment UUID is being extracted correctly on success page. eSewa sends it in `data` parameter, Khalti/Fonepay send it as `payment_uuid` URL param.

**Issue**: Payment verification failing with missing parameters
- **Solution**: Ensure all gateway callback params are passed to verification endpoint. Check `VerifyPaymentRequest` for expected parameters per gateway.

**Issue**: Gateway not available for selected plan currency
- **Solution**: Verify gateway supports the plan's currency in `config/currency.php` → `gateway_support`. Nepal gateways only support NPR.

**Issue**: User context not set after switching organizations
- **Solution**: Ensure frontend calls `POST /api/v1/user/current-organization/{uuid}` after org switch

**Issue**: Payment not attaching plan
- **Solution**: Verify payment status is 'completed' and check `BillingService::attachPlanAfterPayment()` logs

**Issue**: Workspace operations failing with "No active workspace"
- **Solution**: Check that `context:workspace` middleware is used and user has set current workspace

## Production Deployment

### Laravel
1. Optimize autoloader: `composer install --optimize-autoloader --no-dev`
2. Cache config/routes/views: `php artisan optimize`
3. Run queue workers with Supervisor or Laravel Horizon
4. Set up cron for scheduled tasks: `* * * * * php artisan schedule:run`
5. Use Redis for cache/session/queue
6. Enable OPcache and JIT for PHP 8.2+

### Next.js
1. Build: `pnpm build`
2. Deploy to Vercel (recommended) or use `pnpm start` on your server
3. Ensure `NEXT_PUBLIC_API_BASE_URL` points to production Laravel API

### Security Checklist
- Set `APP_DEBUG=false` in Laravel
- Use HTTPS in production
- Enable CORS properly (`config/cors.php`)
- Set secure session cookies
- Configure database backups
- Set up monitoring (Sentry, New Relic, etc.)

## Code Quality Standards

- **Laravel**: PHPStan level 8, Laravel Pint (PSR-12)
- **Next.js**: ESLint with Next.js config, TypeScript strict mode
- **Git Workflow**: Feature branches, PR reviews required
- **Documentation**: Update README when adding major features

## Useful Artisan Commands

```bash
# Create new plan
php artisan tinker
$plan = Plan::create(['name' => 'Pro', 'slug' => 'pro-monthly', ...]);

# Attach plan to organization
$org = Organization::where('uuid', 'xxx')->first();
$plan = Plan::where('slug', 'pro-yearly')->first();
$org->attachPlan($plan);

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Queue management
php artisan queue:work --tries=3
php artisan queue:failed
php artisan queue:retry all
```

## Additional Resources

- Laravel README: `/laravel/README.md`
- Next.js README: `/nextjs/README.md`
- Store README: `/store/README.md`
- API Routes: `/laravel/routes/api/v1.php`
- Plan Configuration: `/laravel/database/seeders/PlanFeaturesSeeder.php`
