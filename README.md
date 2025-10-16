# DJ SaaS Boilerplate

This is a comprehensive SaaS boilerplate for the DJ SaaS product, containing a complete multi-stack application with authentication, organization management, workspaces, user profiles, and integrated billing.

## Project Structure

This boilerplate consists of three main components:

### 1. **Laravel Backend** (`/laravel`)
The backend API built with Laravel, featuring:
- Authentication system
- Organization management
- Workspace management
- User profile management
- Plans and pricing integration
- SureCart integration for billing
- Email and queue configuration

**Configuration:**
- Copy `.env.example` to `.env`
- Configure SureCart credentials
- Set up email provider settings
- Configure queue driver
- See `/laravel/README.md` for detailed setup instructions

### 2. **Next.js Frontend** (`/nextjs`)
Modern UI built with Next.js and Shadcn components:
- Complete UI elements using Shadcn
- User-facing dashboard
- Organization and workspace interfaces
- Integration with Laravel backend API

**Configuration:**
- Copy `.env.example` to `.env.local`
- Ensure `NEXT_PUBLIC_API_URL` points to your Laravel backend URL
- See `/nextjs/README.md` for detailed setup instructions

### 3. **WordPress Billing Store** (`/store`)
WordPress installation with SureCart for payment processing:
- SureCart plugin installed
- Processes all billing operations
- Includes connector plugin that bridges WordPress with the Laravel backend API
- Stores authentication tokens for API communication

**Configuration:**
- Copy `.env.example` to `.env`
- Configure WordPress URLs
- Set correct API endpoints for Laravel backend
- See `/store/README.md` for detailed setup instructions

## Local Development Setup

### Prerequisites
- Local By Flywheel installed
- PHP 8.x+
- Node.js 18+
- Composer
- MySQL

### Installation Steps

1. **Create Local By Flywheel Project**
   - Create a new site in Local By Flywheel
   - Navigate to the project's `public` directory
   - Paste/copy the contents of the `/store` repository into the `public` folder

2. **Configure Laravel Backend**
   ```bash
   cd laravel
   composer install
   cp .env.example .env
   php artisan key:generate
   # Configure .env with database, SureCart, email, and queue settings
   php artisan migrate
   ```

3. **Configure Next.js Frontend**
   ```bash
   cd nextjs
   npm install
   cp .env.example .env.local
   # Ensure backend URL is correctly set
   npm run dev
   ```

4. **Configure WordPress Store**
   - Access your Local By Flywheel site
   - Complete WordPress installation
   - Install and activate SureCart plugin
   - Install and activate the connector plugin
   - Configure `.env` with proper URLs and API endpoints

## Environment Configuration

Each project component has its own environment configuration:

- **Laravel**: `/laravel/.env` (use `.env.example` as template)
- **Next.js**: `/nextjs/.env.local` (use `.env.example` as template)
- **WordPress Store**: `/store/.env` (use `.env.example` as template)

See `build-deploy.yml` for deployment-related environment configurations.

## Key Features

- ✅ Complete authentication system
- ✅ Multi-tenant organization support
- ✅ Workspace management
- ✅ User profile management
- ✅ Plans and pricing with SureCart
- ✅ WordPress connector plugin for seamless API integration
- ✅ Modern UI with Shadcn components
- ✅ Queue and email support

## Additional Resources

For component-specific documentation, refer to:
- `/laravel/README.md` - Laravel backend documentation
- `/nextjs/README.md` - Next.js frontend documentation
- `/store/README.md` - WordPress store documentation

## Deployment

Check the `build-deploy.yml` file for CI/CD configuration and deployment settings.

## Support

For issues and questions, please refer to the individual component documentation or contact the development team.
