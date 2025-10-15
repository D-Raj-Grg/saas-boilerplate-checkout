# Next.js SaaS Boilerplate

A modern, production-ready SaaS boilerplate built with Next.js 15, TypeScript, and shadcn/ui featuring authentication, organizations, workspaces, team management, and more.

## 🚀 Tech Stack

- **Framework:** [Next.js 15.3.5](https://nextjs.org/) (App Router)
- **Language:** [TypeScript 5.8.3](https://www.typescriptlang.org/)
- **Styling:** [Tailwind CSS 4.1.11](https://tailwindcss.com/)
- **UI Components:** [shadcn/ui](https://ui.shadcn.com/) (latest)
- **Package Manager:** [pnpm](https://pnpm.io/)
- **React:** 19.1.0
- **Form Handling:** React Hook Form + Zod validation
- **Authentication:** JWT tokens with secure HTTP-only cookies
- **Linting:** ESLint with Next.js config

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- Node.js 18.17 or later
- pnpm (recommended) or npm/yarn

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone <your-repository-url>
   cd nextjs-saas-boilerplate
   ```

2. **Install dependencies**
   ```bash
   pnpm install
   ```

3. **Set up environment variables**
   Create a `.env.local` file in the root directory:
   ```env
   # API Configuration
   NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1

   # Authentication Configuration
   AUTH_TOKEN_NAME=bsf-auth-token
   ```

4. **Start your backend API**
   Ensure your backend API is running at `http://localhost:8000/api/v1`

## 🚦 Getting Started

1. **Run the development server**
   ```bash
   pnpm dev
   ```

2. **Open your browser**
   Navigate to [http://localhost:3000](http://localhost:3000)

## 📁 Project Structure

```
nextjs-saas-boilerplate/
├── app/                    # Next.js App Router pages and layouts
│   ├── (guest)/           # Public pages (login, signup, pricing, etc.)
│   ├── (auth)/            # Protected pages (dashboard, settings, etc.)
│   ├── layout.tsx         # Root layout
│   ├── page.tsx           # Landing page
│   └── globals.css        # Global styles
├── actions/               # Server actions
│   ├── auth.ts            # Authentication actions
│   ├── organization.ts    # Organization management
│   ├── workspace.ts       # Workspace management
│   └── invitation.ts      # Team invitation actions
├── components/            # React components
│   ├── ui/               # shadcn/ui components
│   └── auth/             # Authentication components
├── lib/                   # Utility functions
│   ├── utils.ts          # Shared utilities
│   ├── auth-client.ts    # Authentication client
│   └── api.ts            # API client wrapper
├── stores/                # Zustand state management
│   └── user-store.ts     # User state store
├── interfaces/            # TypeScript interfaces
├── hooks/                 # Custom React hooks
├── middleware.ts          # Authentication & route middleware
├── .env.local            # Environment variables
├── components.json       # shadcn/ui configuration
├── tailwind.config.ts
├── tsconfig.json
└── package.json
```

## 🔧 Available Scripts

- `pnpm dev` - Start development server
- `pnpm build` - Build for production
- `pnpm start` - Start production server
- `pnpm lint` - Run ESLint
- `pnpm type-check` - Run TypeScript compiler check

## 🎨 Adding UI Components

This project uses shadcn/ui for UI components. To add a new component:

```bash
pnpm dlx shadcn@latest add <component-name>
```

Example:
```bash
pnpm dlx shadcn@latest add button
pnpm dlx shadcn@latest add card
pnpm dlx shadcn@latest add form
```

## ✨ Features

### 🔐 Authentication & Authorization
- **Email/Password Authentication** - Secure login and registration
- **Google OAuth** - Social login integration
- **JWT Token Management** - HTTP-only cookies for secure token storage
- **Protected Routes** - Middleware-based route protection
- **Email Verification** - Verify user email addresses
- **Password Reset** - Forgot password flow

### 🏢 Multi-Tenancy
- **Organizations** - Create and manage multiple organizations
- **Workspaces** - Multiple workspaces per organization
- **Team Management** - Invite and manage team members
- **Role-Based Permissions** - Granular access control
- **Organization Switching** - Seamlessly switch between organizations

### 👥 Team Collaboration
- **Invitations System** - Invite team members via email
- **User Roles** - Admin, Member roles with different permissions
- **Activity Tracking** - Audit logs for team activities

### ⚙️ Settings & Configuration
- **User Profile** - Manage user information
- **Organization Settings** - Configure organization details
- **Workspace Settings** - Manage workspace configuration
- **Danger Zone** - Delete workspace/organization actions

### 💰 Pricing & Billing
- **Pricing Page** - Pre-built pricing tables
- **Plan Comparison** - Feature comparison table
- **Testimonials** - Customer testimonials marquee
- **FAQ Section** - Frequently asked questions

### 🎨 UI/UX
- **Dark Mode** - Built-in theme switching
- **Responsive Design** - Mobile-first approach
- **Modern Components** - shadcn/ui component library
- **Animations** - Framer Motion animations
- **Toast Notifications** - Sonner toast system

## 🔧 Configuration

### Environment Variables

The application uses environment variables for configuration. Key variables include:

- `NEXT_PUBLIC_APP_NAME` - Your application name (displayed throughout the UI)
- `NEXT_PUBLIC_API_BASE_URL` - Backend API base URL
- `AUTH_TOKEN_NAME` - Authentication cookie name

### Customization

1. **Branding** - Update `NEXT_PUBLIC_APP_NAME` in `.env.local`
2. **Logo** - Replace files in `/public/` directory:
   - `logo.svg` - Symbol only
   - `logo-text.svg` - Text only
   - `logo-full.svg` - Full logo with text
3. **Colors** - Modify Tailwind theme in `tailwind.config.ts`
4. **Pricing** - Update pricing data in `/app/(guest)/pricing/_components/`

## 🚀 Deployment

The application can be deployed on various platforms:

### Vercel (Recommended)
```bash
pnpm dlx vercel
```

### Other Platforms
- Build the application: `pnpm build`
- The output will be in the `.next` directory

## 🏗️ Backend API Requirements

This boilerplate requires a backend API that provides the following endpoints:

### Authentication Endpoints
- `POST /auth/login` - User login
- `POST /auth/signup` - User registration
- `POST /auth/google/redirect` - Google OAuth redirect
- `POST /auth/google/callback` - Google OAuth callback
- `POST /auth/logout` - User logout
- `POST /auth/verify-email` - Email verification
- `POST /auth/forgot-password` - Request password reset
- `POST /auth/reset-password` - Reset password

### User Endpoints
- `GET /users/me` - Get current user data
- `PUT /users/me` - Update user profile

### Organization Endpoints
- `GET /organizations` - List organizations
- `POST /organizations` - Create organization
- `PUT /organizations/:id` - Update organization
- `DELETE /organizations/:id` - Delete organization

### Workspace Endpoints
- `GET /workspaces` - List workspaces
- `POST /workspaces` - Create workspace
- `PUT /workspaces/:id` - Update workspace
- `DELETE /workspaces/:id` - Delete workspace

### Invitation Endpoints
- `POST /invitations` - Send invitation
- `GET /invitations` - List invitations
- `POST /invitations/accept` - Accept invitation

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📚 Documentation

For detailed documentation on specific features:

- **Authentication** - See `/actions/auth.ts` for auth flow
- **Organizations & Workspaces** - See `/actions/organization.ts` and `/actions/workspace.ts`
- **API Integration** - See `/lib/api.ts` for API wrapper functions
- **State Management** - See `/stores/user-store.ts` for global state

## 🆘 Support

For support and questions, please open an issue in the GitHub repository.

## 👨‍💻 Author

**Mahavir N**
- Email: mahavirn@bsf.io

## 🙏 Acknowledgments

Built with:
- [Next.js](https://nextjs.org/) - The React Framework
- [shadcn/ui](https://ui.shadcn.com/) - Re-usable components
- [Tailwind CSS](https://tailwindcss.com/) - Utility-first CSS
- [Framer Motion](https://www.framer.com/motion/) - Animation library
- [Zustand](https://zustand-demo.pmnd.rs/) - State management

---

Built with ❤️ using Next.js and TypeScript
