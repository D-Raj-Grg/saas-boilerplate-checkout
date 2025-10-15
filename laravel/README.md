# Laravel SaaS Boilerplate

A comprehensive, production-ready Laravel SaaS boilerplate with multi-tenancy, role-based access control, billing integration, and essential SaaS features.

**Author:** [mahavirn@bsf.io](mailto:mahavirn@bsf.io)

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Project Structure](#project-structure)
- [Architecture](#architecture)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [Customization](#customization)
- [Production Deployment](#production-deployment)
- [Performance](#performance)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### 🏢 Multi-Tenancy
- **Organizations**: Top-level tenant containers with owner management
- **Workspaces**: Sub-tenants within organizations for team collaboration
- **User Management**: Invite and manage team members with role-based permissions
- **Context Switching**: Users can switch between organizations and workspaces
- **Data Isolation**: Complete tenant data isolation at database level

### 🔐 Authentication & Authorization
- **Laravel Sanctum**: Token-based API authentication with expirable tokens
- **Role-Based Access Control (RBAC)**: Organization and workspace-level permissions
- **Email Verification**: Secure email confirmation flow with expirable links
- **Password Reset**: Secure password recovery with token expiration
- **Google OAuth**: Single sign-on integration (ready to extend with other providers)
- **Permission Middleware**: Route-level permission enforcement

### 💳 Billing & Plans
- **SureCart Integration**: Ready-to-use billing system integration
- **Plan Features**: 7 core configurable features per plan
  - Team members limit
  - Workspaces limit
  - Connections per workspace
  - API rate limiting
  - Monthly active users
  - Data retention days
  - Priority support
- **Plan Limits**: Automatic enforcement of feature limits
- **Trial Management**: Automatic trial expiration handling with notifications
- **Plan Enforcement**: Middleware-based feature gating
- **Subscription Webhooks**: Automated plan updates via webhooks
- **Usage Tracking**: Real-time usage tracking for all features

### 👥 Team Collaboration
- **Invitations**: Email-based team invitations with role assignment
- **Workspace Roles**:
  - Manager (full workspace control)
  - Editor (can modify content)
  - Viewer (read-only access)
- **Organization Roles**:
  - Owner (full organization control)
  - Admin (can manage users and settings)
  - Member (basic access)
- **Member Management**: Add, remove, and change roles with permission checks
- **Invitation Expiry**: Configurable invitation expiration (default: 7 days)

### 🔌 Integrations
- **WordPress Connector**: Boilerplate webhook service for WordPress plugins
- **PostHog Analytics**: User tracking and analytics (optional)
- **Email Service**: Branded, responsive email templates
- **Webhook System**: Generic webhook infrastructure for external integrations

### 📧 Waitlist Management
- **Waitlist Signup**: Public waitlist with automatic account creation
- **Status Tracking**: Pending, contacted, converted states
- **Bulk Actions**: Mark multiple entries as contacted/converted
- **CSV Export**: Export waitlist data for analysis
- **Auto-Account Creation**: Scheduled automatic user account creation
- **Email Notifications**: Automatic welcome emails on account creation

### 🎨 Developer Experience
- **RESTful API**: Clean, well-documented API endpoints
- **Type Safety**: Full PHPStan level 8 static analysis coverage
- **Comprehensive Tests**: 410 passing tests with Pest PHP (1666 assertions)
- **Code Quality**: Laravel Pint formatting with PSR-12 standards
- **Rate Limiting**: Configurable rate limits per endpoint group and plan
- **API Versioning**: Versioned API routes (`/api/v1/`)
- **Error Handling**: Consistent error responses across all endpoints
- **Request Validation**: FormRequest classes for all input validation

## 🔧 Requirements

- **PHP**: 8.2 or higher
- **Composer**: 2.x
- **Redis**: 6.x or higher (for caching and queues)
- **Database**: MySQL 8.x or PostgreSQL 14.x
- **Node.js**: 18.x & NPM (for frontend assets)
- **Extensions**:
  - BCMath
  - Ctype
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML

## 📦 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/laravel-saas-boilerplate.git
cd laravel-saas-boilerplate
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database
Update your `.env` file:
```env
APP_NAME="Your SaaS Name"
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. Configure Email
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yoursaas.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 6. Configure Billing (Optional - SureCart)
```env
SURECART_API_KEY=your_api_key
SURECART_API_SECRET=your_api_secret
SURECART_WEBHOOK_SECRET=your_webhook_secret
```

### 7. Configure Analytics (Optional - PostHog)
```env
POSTHOG_API_KEY=your_posthog_key
POSTHOG_HOST=https://app.posthog.com
```

### 8. Run Migrations & Seeders
```bash
php artisan migrate --seed
```

This will create:
- Database tables
- 5 default plans (Free, Early Bird, Starter, Pro, Business)
- Plan features and limits
- Test data (if in development)

### 9. Build Assets
```bash
npm run build
```

## 🚀 Quick Start

### Start Development Server
```bash
# Start all services (Laravel, Queue, Vite)
composer run dev
```

Or start services individually:
```bash
# Laravel server
php artisan serve

# Queue worker (for emails and background jobs)
php artisan queue:work

# Vite dev server (if using Vite for assets)
npm run dev
```

### Create Your First Admin User
```bash
php artisan tinker
```
```php
$user = \App\Models\User::factory()->create([
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);

$org = \App\Models\Organization::factory()->create([
    'name' => 'My Organization',
    'owner_id' => $user->id,
]);

$org->addUser($user, \App\Enums\OrganizationRole::OWNER);

// Attach a plan
$plan = \App\Models\Plan::where('slug', 'pro-yearly')->first();
$org->attachPlan($plan);

echo "Admin user created!\n";
echo "Email: admin@example.com\n";
echo "Password: password\n";
```

### Access the Application

- **API Base URL**: `http://localhost:8000/api/v1`
- **Health Check**: `http://localhost:8000/api/v1/health`
- **Web Status**: `http://localhost:8000/status`

## 📁 Project Structure

```
laravel-saas-boilerplate/
├── app/
│   ├── Console/           # Artisan commands
│   ├── Enums/             # PHP 8.1+ Enums
│   │   ├── OrganizationRole.php
│   │   └── WorkspaceRole.php
│   ├── Events/            # Event classes
│   ├── Exceptions/        # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/   # API Controllers
│   │   │   └── Api/V1/    # V1 API endpoints
│   │   ├── Middleware/    # Custom middleware
│   │   │   ├── EnsureOrganizationSelected.php
│   │   │   ├── EnsureWorkspaceSelected.php
│   │   │   ├── RateLimitHeaders.php
│   │   │   └── WebHeaders.php
│   │   └── Requests/      # Form Request validation
│   ├── Jobs/              # Queue jobs
│   ├── Listeners/         # Event listeners
│   ├── Mail/              # Mailable classes
│   │   ├── InvitationMail.php
│   │   ├── PasswordResetMail.php
│   │   ├── TrialExpirationWarningMail.php
│   │   ├── WaitlistAccountCreatedMail.php
│   │   ├── WelcomeMail.php
│   │   └── EmailVerificationMail.php
│   ├── Models/            # Eloquent models
│   │   ├── Connection.php
│   │   ├── Invitation.php
│   │   ├── Organization.php
│   │   ├── Plan.php
│   │   ├── User.php
│   │   ├── Waitlist.php
│   │   └── Workspace.php
│   ├── Notifications/     # Notification classes
│   ├── Observers/         # Model observers
│   ├── Policies/          # Authorization policies
│   ├── Providers/         # Service providers
│   ├── Services/          # Business logic services
│   │   ├── OrganizationService.php
│   │   ├── PlanValidationService.php
│   │   ├── PostHogService.php
│   │   ├── SureCartService.php
│   │   ├── WordPressWebhookService.php
│   │   └── WorkspaceService.php
│   └── Traits/            # Reusable traits
│       ├── HasPricingPlan.php
│       ├── HasSlug.php
│       └── HasUuid.php
├── bootstrap/
│   └── app.php            # Application bootstrap
├── config/                # Configuration files
│   ├── workspace-permissions.php
│   └── ...
├── database/
│   ├── factories/         # Model factories
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
│       ├── PlanSeeder.php
│       └── PlanFeaturesSeeder.php
├── public/                # Public assets
│   └── images/
│       └── logo.png       # Replace with your logo
├── resources/
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript
│   └── views/
│       └── emails/        # Email templates
│           ├── layout.blade.php
│           ├── welcome.blade.php
│           ├── invitation.blade.php
│           └── ...
├── routes/
│   ├── api.php            # API routes (v1)
│   ├── console.php        # Artisan commands
│   └── web.php            # Web routes
├── storage/               # Application storage
├── tests/
│   ├── Feature/           # Feature tests
│   │   ├── Api/V1/        # API endpoint tests
│   │   └── Mail/          # Email tests
│   └── Unit/              # Unit tests
│       └── Traits/        # Trait tests
├── .env.example           # Environment example
├── .gitignore             # Git ignore rules
├── composer.json          # PHP dependencies
├── package.json           # Node dependencies
├── phpstan.neon           # PHPStan configuration
├── phpunit.xml            # PHPUnit configuration
└── README.md              # This file
```

## 🏗 Architecture

### Multi-Tenancy Structure
```
Organization (Top-level Tenant)
├── Owner (User who created the org)
├── Plan & Subscription
├── Users (Organization Members)
│   └── OrganizationRole (Owner/Admin/Member)
├── Invitations (Pending invites)
├── Workspaces (Sub-tenants)
│   ├── Users (Workspace Members)
│   │   └── WorkspaceRole (Manager/Editor/Viewer)
│   ├── Connections (External integrations)
│   └── Settings (Workspace-specific config)
└── Usage Tracking (Feature consumption)
```

### Permission System

**Organization-Level Roles:**
- `Owner`: Full control over organization, billing, and all workspaces
- `Admin`: Can manage users, workspaces, and settings (cannot delete org)
- `Member`: Basic access to assigned workspaces

**Workspace-Level Roles:**
- `Manager`: Full control over workspace and can manage members
- `Editor`: Can modify workspace content and settings
- `Viewer`: Read-only access to workspace

**Permission Configuration:**
See `config/workspace-permissions.php` for detailed permission mappings.

### Key Models

| Model | Description | Key Relationships |
|-------|-------------|-------------------|
| `User` | Authentication and user data | belongsToMany: Organization, Workspace |
| `Organization` | Top-level tenant | hasMany: Workspace, User, Invitation |
| `Workspace` | Sub-tenant within organization | belongsTo: Organization; hasMany: Connection |
| `Invitation` | Team invitation management | belongsTo: Organization, Inviter (User) |
| `Connection` | External service integrations | belongsTo: Workspace |
| `Waitlist` | Waitlist signups | - |
| `Plan` | Subscription plans | hasMany: PlanLimit |
| `PlanLimit` | Feature limits per plan | belongsTo: Plan |
| `PlanFeature` | Available features catalog | - |
| `OrganizationFeatureUsage` | Usage tracking | belongsTo: Organization |

### Database Schema

- **UUID Primary Keys**: All models use UUIDs for security and distribution
- **Soft Deletes**: Most models support soft deletion for audit trails
- **Timestamps**: Created/updated timestamps on all models
- **Indexes**: Optimized indexes on foreign keys and frequently queried columns

## 📚 API Documentation

### Base URL
```
Production: https://your-domain.com/api/v1
Development: http://localhost:8000/api/v1
```

### Authentication

All authenticated endpoints require a Bearer token:
```bash
Authorization: Bearer {your-api-token}
```

### Authentication Endpoints

#### Register
```bash
POST /api/v1/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

Response: 201 Created
{
  "success": true,
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

#### Login
```bash
POST /api/v1/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}

Response: 200 OK
{
  "success": true,
  "data": {
    "user": { ... },
    "token": "2|def456..."
  }
}
```

#### Get Authenticated User
```bash
GET /api/v1/me
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "data": {
    "id": "uuid",
    "name": "John Doe",
    "email": "john@example.com",
    "current_organization_id": "org-uuid",
    "current_workspace_id": "workspace-uuid"
  }
}
```

#### Logout
```bash
POST /api/v1/logout
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "message": "Logged out successfully"
}
```

### Organization Management

#### List Organizations
```bash
GET /api/v1/organizations
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "data": [
    {
      "uuid": "org-uuid",
      "name": "My Organization",
      "role": "owner"
    }
  ]
}
```

#### Create Organization
```bash
POST /api/v1/organization
Authorization: Bearer {token}

{
  "name": "New Organization",
  "description": "Optional description"
}

Response: 201 Created
```

#### Get Current Organization
```bash
GET /api/v1/organization
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "data": {
    "uuid": "org-uuid",
    "name": "My Organization",
    "plan": { ... },
    "members_count": 5
  }
}
```

#### Switch Organization
```bash
POST /api/v1/organization/switch
Authorization: Bearer {token}

{
  "organization_uuid": "org-uuid"
}

Response: 200 OK
```

### Workspace Management

#### List Workspaces
```bash
GET /api/v1/organization/workspaces
Authorization: Bearer {token}

Response: 200 OK
{
  "success": true,
  "data": [
    {
      "uuid": "workspace-uuid",
      "name": "My Workspace",
      "role": "manager"
    }
  ]
}
```

#### Create Workspace
```bash
POST /api/v1/organization/workspace
Authorization: Bearer {token}

{
  "name": "My Workspace",
  "description": "Workspace description"
}

Response: 201 Created
```

#### Add Member to Workspace
```bash
POST /api/v1/workspace/members
Authorization: Bearer {token}

{
  "user_uuid": "user-uuid",
  "role": "editor"
}

Response: 201 Created
```

### Team Invitations

#### Invite User
```bash
POST /api/v1/invitations
Authorization: Bearer {token}

{
  "email": "newuser@example.com",
  "role": "member",
  "workspace_assignments": [
    {
      "workspace_id": "workspace-uuid",
      "role": "editor"
    }
  ]
}

Response: 201 Created
```

#### Accept Invitation
```bash
POST /api/v1/invitations/{token}/accept

Response: 200 OK
```

### Rate Limits

| Plan | Requests/Min | Endpoints |
|------|--------------|-----------|
| Free | 60 | All endpoints |
| Starter | 120 | All endpoints |
| Pro | 300 | All endpoints |
| Business | 600 | All endpoints |

Rate limit headers are included in all responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

## 🧪 Testing

### Test Statistics
- **Total Tests**: 410
- **Total Assertions**: 1666
- **Code Coverage**: Available with `--coverage` flag
- **Test Suites**: Feature, Unit
- **Test Framework**: Pest PHP

### Run All Tests
```bash
# Sequential execution
php artisan test

# Parallel execution (faster, recommended)
php artisan test --parallel

# With code coverage
php artisan test --coverage

# With coverage and minimum threshold
php artisan test --coverage --min=80
```

### Run Specific Test Suites
```bash
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit

# Specific test file
php artisan test tests/Feature/Api/V1/OrganizationControllerTest.php

# Filter by test name
php artisan test --filter=invitation
```

### Test Structure
```
tests/
├── Feature/                      # Integration tests
│   ├── Api/V1/                   # API endpoint tests
│   │   ├── AuthControllerTest.php
│   │   ├── OrganizationControllerTest.php
│   │   ├── WorkspaceControllerTest.php
│   │   ├── InvitationControllerTest.php
│   │   └── ...
│   ├── Mail/                     # Email tests
│   │   └── WaitlistAccountCreatedMailTest.php
│   └── ...
└── Unit/                         # Unit tests
    └── Traits/                   # Trait tests
        └── HasPricingPlanTest.php
```

### Writing Tests

Example feature test:
```php
use function Pest\Laravel\{postJson, getJson};

test('user can create organization', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = postJson('/api/v1/organization', [
        'name' => 'Test Organization',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.name'))->toBe('Test Organization');
});
```

## 🔍 Code Quality

### Static Analysis (PHPStan)
```bash
# Analyze code (Level 8)
composer analyze

# Analyze specific paths
./vendor/bin/phpstan analyse app

# Generate baseline (for existing issues)
composer baseline
```

### Code Formatting (Laravel Pint)
```bash
# Format all code
./vendor/bin/pint

# Check formatting without changes
./vendor/bin/pint --test

# Format specific directory
./vendor/bin/pint app/Services
```

### Run All Quality Checks
```bash
# Runs Pint, PHPStan, and Tests
composer test
```

### Pre-commit Hooks (Optional)

Create `.git/hooks/pre-commit`:
```bash
#!/bin/sh
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
php artisan test --parallel
```

## 🎨 Customization

### Branding

1. **Application Name**
   Update `APP_NAME` in `.env`:
   ```env
   APP_NAME="Your SaaS Name"
   ```
   This dynamically updates all email templates and API responses.

2. **Logo**
   Replace `public/images/logo.png` with your logo (recommended: PNG, 200x60px)

3. **Email Templates**
   Customize templates in `resources/views/emails/`:
   - `layout.blade.php` - Base email layout
   - `welcome.blade.php` - Welcome email
   - `invitation.blade.php` - Team invitation
   - `email-verification.blade.php` - Email verification
   - `password-reset.blade.php` - Password reset
   - `trial-expiration-warning.blade.php` - Trial expiration
   - `waitlist-account-created.blade.php` - Waitlist signup

### Plans and Features

**Available Features** (configured in `PlanFeaturesSeeder.php`):
1. `team_members` - Number of team members per organization
2. `workspaces` - Number of workspaces per organization
3. `connections_per_workspace` - External service connections per workspace
4. `api_rate_limit` - API requests per minute
5. `unique_visitors` - Monthly active users limit
6. `data_retention_days` - Days of data retention
7. `priority_support` - Boolean feature for priority support

**Modify Plan Limits**:

Edit `database/seeders/PlanFeaturesSeeder.php`:
```php
$planLimits = [
    'free' => [
        'team_members' => '5',
        'workspaces' => '1',
        'connections_per_workspace' => '1',
        'api_rate_limit' => '60',
        'unique_visitors' => '1000',
        'data_retention_days' => '7',
        'priority_support' => 'false',
    ],
    'pro-yearly' => [
        'team_members' => '50',
        'workspaces' => '10',
        'connections_per_workspace' => '10',
        'api_rate_limit' => '300',
        'unique_visitors' => '50000',
        'data_retention_days' => '90',
        'priority_support' => 'true',
    ],
];
```

**Add Custom Features**:
1. Add to `PlanFeaturesSeeder.php` features array
2. Update plan limits accordingly
3. Implement feature checking in your code:
   ```php
   if ($organization->hasFeature('custom_feature')) {
       // Feature is available
   }

   $limit = $organization->getLimit('custom_feature');
   if ($organization->canUse('custom_feature', $requiredAmount)) {
       // Can use feature
   }
   ```

### Permissions

Customize workspace permissions in `config/workspace-permissions.php`:

```php
return [
    WorkspaceRole::MANAGER->value => [
        'workspace.view',
        'workspace.manage_settings',
        'workspace.manage_members',
        'workspace.manage_connections',
        'workspace.delete',
        // Add more permissions
    ],
    WorkspaceRole::EDITOR->value => [
        'workspace.view',
        'workspace.manage_settings',
        'workspace.manage_connections',
        // Add more permissions
    ],
    WorkspaceRole::VIEWER->value => [
        'workspace.view',
    ],
];
```

### WordPress Integration

Customize the WordPress webhook service in `app/Services/WordPressWebhookService.php`:

```php
public function customSync(Connection $connection, array $data): bool
{
    $payload = [
        'action' => 'custom_sync',
        'connection_id' => $connection->uuid,
        'data' => $data,
    ];

    return $this->sendWebhook($connection, $payload, 'sync.custom');
}
```

Register webhook endpoint in `routes/api.php`:
```php
Route::post('/webhook/wordpress/{connection}', [WordPressWebhookController::class, 'handle']);
```

## 🚀 Production Deployment

### Environment Configuration
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Use Redis for better performance
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Enable query logging in production (optional)
DB_QUERY_LOG=false
```

### Optimization Commands
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Run all optimizations
php artisan optimize
```

### Queue Workers

**Using Supervisor** (recommended):

Create `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

**Using Laravel Horizon**:
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

### Scheduled Tasks

Add to crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks (defined in `app/Console/Kernel.php`):
- Trial expiration checks
- Waitlist processing
- Usage tracking cleanup
- Email queue processing

### Web Server Configuration

**Nginx Configuration**:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Security Checklist

- [x] Set `APP_DEBUG=false` in production
- [x] Use strong `APP_KEY` (auto-generated)
- [x] Configure CORS properly in `config/cors.php`
- [x] Enable rate limiting (already configured)
- [x] Use HTTPS in production (configure SSL/TLS)
- [x] Set proper file permissions (755 for directories, 644 for files)
- [x] Enable CSRF protection (enabled by default)
- [x] Set secure session cookies in `config/session.php`
- [x] Configure database backups (use your hosting provider)
- [x] Set up monitoring and error tracking (Sentry, Bugsnag, etc.)
- [x] Enable Redis password authentication
- [x] Configure firewall rules
- [x] Keep dependencies updated regularly

### Monitoring

**Recommended Tools**:
- **Error Tracking**: Sentry, Flare, Bugsnag
- **Application Performance**: New Relic, Blackfire
- **Uptime Monitoring**: Pingdom, UptimeRobot
- **Log Management**: Papertrail, Logtail

**Laravel Telescope** (Development):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

## ⚡ Performance

### Caching Strategy
- **Configuration**: Cached in production (`config:cache`)
- **Routes**: Cached in production (`route:cache`)
- **Views**: Compiled and cached (`view:cache`)
- **User Sessions**: Stored in Redis for fast access
- **Permission Checks**: Cached per request to reduce database queries
- **Query Results**: Manual caching for expensive queries

### Database Optimization
- **Indexes**: Optimized indexes on foreign keys and frequently queried columns
- **Composite Indexes**: Multi-column indexes for common query patterns
- **Soft Deletes**: Audit trail without data loss
- **UUID Primary Keys**: Security and horizontal scaling
- **Eager Loading**: Prevent N+1 query problems
- **Database Connection Pooling**: Configured in `config/database.php`

### Queue Optimization
- **Background Jobs**: Long-running tasks moved to queues
- **Email Sending**: All emails sent via queue
- **Webhook Delivery**: External API calls queued
- **Batch Jobs**: Bulk operations split into smaller chunks
- **Failed Job Retry**: Automatic retry with exponential backoff
- **Job Prioritization**: Different queues for different priorities

### Redis Configuration
```env
REDIS_CLIENT=phpredis  # Faster than predis
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3
```

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### Getting Started
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Write tests for your changes
5. Ensure all tests pass (`php artisan test --parallel`)
6. Run code quality checks (`./vendor/bin/pint && composer analyze`)
7. Commit your changes (`git commit -m 'Add amazing feature'`)
8. Push to your branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

### Development Guidelines
- **PSR-12**: Follow PSR-12 coding standards
- **Type Safety**: Use strict types and type hints
- **Tests**: Write comprehensive tests (aim for >80% coverage)
- **Documentation**: Update README and inline documentation
- **Single Purpose**: Keep methods focused and single-purpose
- **Meaningful Names**: Use descriptive variable and method names
- **Comments**: Comment complex logic, not obvious code
- **DRY**: Don't Repeat Yourself - extract reusable code

### Pull Request Process
1. Ensure your PR description clearly describes the problem and solution
2. Include relevant issue numbers if applicable
3. Update the README.md with details of changes if needed
4. The PR will be merged once you have approval from maintainers

### Code Review Checklist
- [ ] Code follows project style guidelines
- [ ] Tests are included and passing
- [ ] Documentation is updated
- [ ] No breaking changes (or clearly documented)
- [ ] Commit messages are clear and descriptive

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### MIT License Summary
- ✅ Commercial use
- ✅ Modification
- ✅ Distribution
- ✅ Private use
- ⚠️ Liability and warranty disclaimer

## 🙏 Credits

**Author:** [mahavirn@bsf.io](mailto:mahavirn@bsf.io)

**Built With:**
- [Laravel 11](https://laravel.com) - The PHP framework
- [Laravel Sanctum](https://laravel.com/docs/sanctum) - API authentication
- [Pest PHP](https://pestphp.com) - Testing framework
- [PHPStan](https://phpstan.org) - Static analysis
- [Laravel Pint](https://laravel.com/docs/pint) - Code formatter
- [SureCart](https://surecart.com) - Billing integration
- [PostHog](https://posthog.com) - Product analytics (optional)

## 📞 Support

- **Email**: [mahavirn@bsf.io](mailto:mahavirn@bsf.io)
- **Documentation**: See `/docs` directory
- **Issues**: [GitHub Issues](https://github.com/yourusername/laravel-saas-boilerplate/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/laravel-saas-boilerplate/discussions)

## ✅ What's Included

- ✅ Multi-tenant architecture (Organizations → Workspaces → Users)
- ✅ Complete authentication system with email verification
- ✅ Role-based access control with granular permissions
- ✅ Billing integration with SureCart
- ✅ Team collaboration with invitations
- ✅ WordPress connector boilerplate
- ✅ Waitlist management system
- ✅ Responsive, branded email templates
- ✅ Rate limiting and security middleware
- ✅ Comprehensive test suite (410 tests, 1666 assertions)
- ✅ PHPStan level 8 static analysis
- ✅ Production-ready queue system
- ✅ Developer-friendly RESTful API
- ✅ Usage tracking and plan enforcement
- ✅ API versioning (v1)
- ✅ Error handling and logging

## ❌ What's NOT Included (Customize These)

- ❌ Frontend UI (this is an API-only boilerplate)
- ❌ Specific WordPress plugin logic
- ❌ Custom business logic for your SaaS
- ❌ Payment gateways besides SureCart
- ❌ Specific integrations (extend the Connection model)
- ❌ Admin dashboard UI
- ❌ Customer support ticketing system

## 🗺 Roadmap

- [ ] Audit log system for compliance
- [ ] Advanced reporting dashboard
- [ ] Webhook management UI
- [ ] API rate limit customization per plan
- [ ] Two-factor authentication (2FA)
- [ ] Activity feeds and notifications
- [ ] File upload/storage system (S3, etc.)
- [ ] Real-time notification system (WebSockets)
- [ ] Advanced analytics and metrics
- [ ] API documentation UI (Swagger/OpenAPI)
- [ ] Multi-language support (i18n)
- [ ] Mobile app starter kit

---

**Ready to build your SaaS?** Get started in minutes! 🚀

For questions or support, contact: [mahavirn@bsf.io](mailto:mahavirn@bsf.io)
