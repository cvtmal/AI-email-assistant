# AI Mail Assistant

An email assistant application that reads emails from an IMAP inbox, displays them in a web UI, allows for AI-generated replies with conversational context, and sends responses via SMTP.

## Features

- **IMAP Integration**: Connect to any IMAP email server to read inbox messages
- **AI-Powered Replies**: Use AI to generate contextual email replies with refinement options
- **Conversational Context**: Continue refining AI responses with natural language instructions
- **SMTP Integration**: Send replies directly via SMTP
- **Multi-Account Support**: Use multiple email accounts with the application
- **Email Activity Monitoring**: Track email sending statistics and activity across accounts
- **Modern UI**: Clean interface built with Laravel 12, Inertia.js, and React

## Tech Stack

- Laravel 12
- PHP 8.4
- Inertia.js & React 19 for frontend
- TypeScript with TailwindCSS 4
- webklex/laravel-imap for legacy IMAP connectivity
- directorytree/imapengine-laravel for new IMAP implementation
- Laravel Mail for SMTP
- OpenAI GPT-4 integration (or compatible API)
- Pest PHP for testing

## Setup Instructions

1. **Clone the repository**

   ```bash
   git clone https://github.com/cvtmal/aimail
   cd aimail
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**

   ```bash
   npm install
   ```

4. **Copy environment file and configure**

   ```bash
   cp .env.example .env
   ```

   Update the following sections in your `.env` file:

   - IMAP settings:
     ```
     IMAP_HOST=imap.example.com
     IMAP_PORT=993
     IMAP_ENCRYPTION=ssl
     IMAP_USERNAME=your-username
     IMAP_PASSWORD=your-password
     ```

   - SMTP settings (for multiple accounts):
     ```
     # Default account
     SMTP_HOST=smtp.example.com
     SMTP_PORT=587
     SMTP_USERNAME=your-username
     SMTP_PASSWORD=your-password
     SMTP_ENCRYPTION=tls
     MAIL_FROM_ADDRESS=default@example.com
     MAIL_FROM_NAME="Default Account"
     
     # Additional account 1
     SMTP1_MAIL_HOST=smtp1.example.com
     SMTP1_MAIL_PORT=587
     SMTP1_MAIL_USERNAME=username1
     SMTP1_MAIL_PASSWORD=password1
     SMTP1_MAIL_ENCRYPTION=tls
     SMTP1_MAIL_FROM_ADDRESS=account1@example.com
     SMTP1_MAIL_FROM_NAME="Account 1"
     
     # Additional account 2
     SMTP2_MAIL_HOST=smtp2.example.com
     SMTP2_MAIL_PORT=587
     SMTP2_MAIL_USERNAME=username2
     SMTP2_MAIL_PASSWORD=password2
     SMTP2_MAIL_ENCRYPTION=tls
     SMTP2_MAIL_FROM_ADDRESS=account2@example.com
     SMTP2_MAIL_FROM_NAME="Account 2"
     ```

   - AI API settings:
     ```
     AI_API_URL=https://api.openai.com/v1/chat/completions
     AI_API_KEY=your-openai-api-key
     ```

5. **Generate application key**

   ```bash
   php artisan key:generate
   ```

6. **Run database migrations**

   ```bash
   php artisan migrate
   ```

7. **Seed development data (optional)**

   ```bash
   php artisan db:seed --class=EmailReplySeeder
   ```

8. **Build frontend assets**

   ```bash
   npm run build
   ```

9. **Start the development server**

   ```bash
   composer run dev
   ```

## Development Commands

### Backend (Laravel)
- `composer run dev` - Start development server with queue, logs, and Vite (recommended)
- `composer run dev:ssr` - Start development server with SSR enabled
- `composer run test` - Run PHP tests (config clear + test)
- `php artisan serve` - Start Laravel development server only
- `php artisan queue:listen --tries=1` - Start queue worker
- `php artisan pail --timeout=0` - Real-time log monitoring
- `php artisan migrate` - Run database migrations
- `php artisan db:seed --class=EmailReplySeeder` - Seed development data

### Frontend (React/TypeScript)
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check code formatting
- `npm run types` - TypeScript type checking without emit

### Testing
- `composer run test` - Run all PHP tests using Pest
- Tests use SQLite in-memory database for speed
- Feature tests in `tests/Feature/`, Unit tests in `tests/Unit/`

## Development Notes

- For local development without an actual IMAP connection, the app will use a mock IMAP client that provides sample emails
- The `.env` configuration determines whether to use the real or mock IMAP client
- Tests are written using Pest PHP
- ImapEngine tests are currently skipped due to memory issues with the IMAP package during testing
- Uses CarbonImmutable for date handling throughout the application

## Multi-Account Support

### Configuration

1. **IMAP Accounts**

   Multiple IMAP accounts are configured in:
   - `config/imap.php` - Legacy IMAP client configuration
   - `config/imapengine.php` - New ImapEngine client configuration with multiple mailboxes

2. **SMTP Accounts**

   Multiple SMTP accounts are defined in `config/mail.php` under the 'mailers' array:
   - `smtp` (default account)
   - `smtp1` (additional account 1)
   - `smtp2` (additional account 2)

3. **Email Signatures**

   Account-specific email signatures are configured in `config/signatures.php`

### Usage in Backend

The system supports account-specific operations throughout the application. All key services accept an optional account identifier:

```php
// Using the default account
$emails = $imapClient->getInboxEmails();

// Using a specific account
$emails = $imapClient->getInboxEmails('damian');
$mailerService->sendReply($email, $combined, 'damian');
```

### Database Schema

The `email_replies` table includes an `account` column that stores which account each email reply belongs to, enabling proper filtering and organization.

### Routes

The application includes account-specific routes:
- Default routes: `/inbox/*`, `/imapengine-inbox/*`
- Account-specific routes: `/accounts/{account}/*`
- Email activity monitoring: `/email-activity`, `/api/email-activity`

## Architecture Overview

This is an AI-powered email assistant application built with Laravel 12 + Inertia.js + React that handles IMAP email reading, AI-generated replies, and SMTP sending.

### Backend Structure (Laravel)
- **Controllers**: `ImapEngineInboxController` (new IMAP engine) and `InboxController` (legacy)
- **Services**: 
  - `AIClient` - OpenAI GPT-4 integration for generating email replies
  - `ImapEngineClient` - New IMAP client using directorytree/imapengine-laravel
  - `ImapClient` - Legacy IMAP client using webklex/laravel-imap
  - `MailerService` - SMTP email sending with multi-account support
  - `SignatureService` - Email signature management
- **Models**: `EmailReply` (stores AI replies and chat history), `User`
- **Contracts**: Interface-based architecture for services (AIClientInterface, etc.)

### Frontend Structure (React/TypeScript)
- **Framework**: Inertia.js with React 19, TypeScript, TailwindCSS 4
- **UI Components**: Radix UI primitives with custom styled components in `components/ui/`
- **Pages**: `ImapEngineInbox/` (new implementation), `Inbox/` (legacy), auth pages
- **Layouts**: `AppLayout` with sidebar navigation, auth layouts
- **State Management**: Inertia forms with React hooks
- **Styling**: TailwindCSS with dark mode support

### Key Features
- **IMAP Integration**: Connect to multiple email accounts, read inbox messages
- **AI-Powered Replies**: Generate contextual email replies using GPT-4 with conversational refinement
- **Chat History**: Maintain conversation context between AI interactions
- **SMTP Sending**: Send replies via configured SMTP accounts
- **Multi-Account**: Support for multiple email accounts with account-specific configurations

## Testing

```bash
composer run test
```

### Test Coverage
- **Authentication & Authorization**: User registration, login, password reset
- **Email Activity Controller**: Statistics, filtering, API endpoints
- **ImapEngine Controller**: Currently skipped due to package memory issues
- Uses Pest PHP testing framework with SQLite in-memory database

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
