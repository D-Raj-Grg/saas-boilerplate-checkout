# DJ SaaS Boilerplate

This is a comprehensive SaaS boilerplate for the DJ SaaS product, containing a complete multi-stack application with authentication, organization management, workspaces, user profiles, and integrated Nepal payment gateways.

## Project Structure

This boilerplate consists of two main components:

### 1. **Laravel Backend** (`/laravel`)
The backend API built with Laravel, featuring:
- Authentication system
- Organization management
- Workspace management
- User profile management
- Plans and pricing integration
- Nepal payment gateway integration (eSewa, Khalti, Fonepay)
- Email and queue configuration

**Configuration:**
- Copy `.env.example` to `.env`
- Configure payment gateway credentials (eSewa, Khalti, Fonepay)
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


## Local Development Setup

### Prerequisites
- Local By Flywheel installed
- PHP 8.x+
- Node.js 18+
- Composer
- MySQL

### Installation Steps

1. **Configure Laravel Backend**
   ```bash
   cd laravel
   composer install
   cp .env.example .env
   php artisan key:generate
   # Configure .env with database, payment gateways, email, and queue settings
   php artisan migrate
   ```

2. **Configure Next.js Frontend**
   ```bash
   cd nextjs
   npm install
   cp .env.example .env.local
   # Ensure backend URL is correctly set
   npm run dev
   ```


## Environment Configuration

Each project component has its own environment configuration:

- **Laravel**: `/laravel/.env` (use `.env.example` as template)
- **Next.js**: `/nextjs/.env.local` (use `.env.example` as template)

See `build-deploy.yml` for deployment-related environment configurations.

## Key Features

- ✅ Complete authentication system
- ✅ Multi-tenant organization support
- ✅ Workspace management
- ✅ User profile management
- ✅ Plans and pricing with Nepal payment gateways
- ✅ In-app checkout and payment processing
- ✅ Modern UI with Shadcn components
- ✅ Queue and email support

## Additional Resources

For component-specific documentation, refer to:
- `/laravel/README.md` - Laravel backend documentation
- `/nextjs/README.md` - Next.js frontend documentation

## Deployment

Check the `build-deploy.yml` file for CI/CD configuration and deployment settings.

## Support

For issues and questions, please refer to the individual component documentation or contact the development team.
