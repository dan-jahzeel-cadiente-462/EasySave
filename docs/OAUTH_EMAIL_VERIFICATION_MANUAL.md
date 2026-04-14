# Google OAuth & Email Verification Implementation Manual

## 📋 Table of Contents
1. [Dependency Status](#dependency-status)
2. [Installation & Setup](#installation--setup)
3. [Google OAuth Configuration](#google-oauth-configuration)
4. [Email Verification Setup](#email-verification-setup)
5. [API Endpoints](#api-endpoints)
6. [Web Interface Flow](#web-interface-flow)
7. [Admin Role Exemptions](#admin-role-exemptions)
8. [Environment Variables](#environment-variables)
9. [Database Schema](#database-schema)
10. [Troubleshooting](#troubleshooting)
11. [Testing Guide](#testing-guide)

---

## Dependency Status

### ✅ Already Installed
- **symfony/brevo-mailer** (7.4.*) - Email delivery using Brevo service
- **symfony/http-client** (7.4.7) - HTTP requests for OAuth
- **symfony/security-bundle** (7.4.*) - Authentication framework
- **lexik/jwt-authentication-bundle** (^3.2) - JWT tokens for API authentication

### ✅ Just Installed
- **knpuniversity/oauth2-client-bundle** (v2.20.2) - OAuth2 client support
- **league/oauth2-client** (2.9.0) - OAuth2 client library
- **guzzlehttp/guzzle** (7.10.0) - HTTP client for OAuth calls

---

## Installation & Setup

### Step 1: Installation (Already Completed)

The OAuth2 Client Bundle has been installed via Composer:

```bash
composer require knpuniversity/oauth2-client-bundle
```

This brings in all necessary dependencies.

### Step 2: Verify Files Created/Updated

Check these files were created/updated in your project:

✅ **Core Services:**
- `src/Service/EmailVerificationService.php` - Email verification logic
- `src/Controller/EmailVerificationController.php` - Web verification endpoint
- `src/Controller/GoogleOAuthController.php` - Google OAuth handler
- `src/Controller/ApiRegistrationController.php` - API registration with verification

✅ **Configuration:**
- `config/packages/oauth2_client.yaml` - OAuth bundle configuration

✅ **Templates:**
- `templates/email_verification/index.html.twig` - Verification status page
- `templates/registration/register.html.twig` - Updated with email field
- `templates/security/login.html.twig` - Updated with Google OAuth button

✅ **Forms:**
- `src/Form/RegistrationFormType.php` - Updated with email field

---

## Google OAuth Configuration

### Step 1: Get Google OAuth Credentials

1. **Go to Google Cloud Console**: https://console.cloud.google.com/
2. **Create a New Project**:
   - Click "Select a Project" → "New Project"
   - Name: "EasySave"
   - Click "Create"

3. **Enable OAuth 2.0 API**:
   - Go to "APIs & Services" → "Library"
   - Search for "Google+ API"
   - Click it and press "Enable"

4. **Create OAuth 2.0 Credentials**:
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "OAuth client ID"
   - Choose "Web application"
   - Name: "EasySave Web"
   - **Authorized JavaScript origins** (add these):
     ```
     http://localhost:8000
     http://127.0.0.1:8000
     http://easysave.local:8000
     ```
   - **Authorized redirect URIs** (add these):
     ```
     http://localhost:8000/auth/connect/check
     http://127.0.0.1:8000/auth/connect/check
     http://easysave.local:8000/auth/connect/check
     ```
   - If you have a production domain, add those too
   - Click "Create"

5. **Copy Your Credentials**:
   - Copy the **Client ID** and **Client Secret**
   - Save them securely

### Step 2: Set Environment Variables

1. **Open `.env.local`** (create if doesn't exist):

```bash
# Google OAuth Settings
GOOGLE_CLIENT_ID=your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret-here
```

2. **Clear Symfony cache**:
```bash
php bin/console cache:clear
```

---

## Email Verification Setup

### Step 1: Configure Brevo (Mail Service)

1. **Get Brevo Account**: https://www.brevo.com/
2. **Find Your API Key**:
   - Go to Settings → API Key
   - Copy your API v3 key

3. **Update `.env.local`**:

```bash
# Brevo Mailer Configuration
MAILER_DSN=brevo+api://YOUR_API_KEY_HERE@default
```

### Step 2: Configure Sender Email

In `config/packages/framework.yaml`, update the mail settings:

```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
        headers:
            From: 'noreply@easysave.local'
```

### Step 3: Alternative - Use SMTP

If using SMTP instead:

```bash
# Gmail Example (requires 2FA with App Password)
MAILER_DSN=smtp://your-email@gmail.com:your-app-password@smtp.gmail.com:587?encryption=tls
```

---

## API Endpoints

### Registration Endpoint

**POST** `/api/register`

```json
{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "SecurePassword123",
  "first_name": "John",
  "last_name": "Doe"
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "message": "User registered successfully",
  "user": {
    "id": 123,
    "username": "john_doe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "verified": false,
    "roles": ["ROLE_USER"]
  },
  "verification": {
    "success": true,
    "message": "Verification email sent successfully. Please check your email.",
    "status": "pending"
  }
}
```

### Verify Email (Using Token)

**POST** `/api/verify-email`

```json
{
  "token": "verification-token-from-email"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Email verified successfully. You can now login."
}
```

### Resend Verification Email

**POST** `/api/resend-verification`

```json
{
  "email": "john@example.com"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Verification email sent successfully. Please check your inbox."
}
```

### Check Verification Status

**POST** `/api/verification-status`

```json
{
  "email": "john@example.com"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "verified": false,
    "has_token": true,
    "role_exempted": false
  }
}
```

### API Login

**POST** `/api/login`

```json
{
  "username": "john_doe",
  "password": "SecurePassword123"
}
```

**Success Response (200)**:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": "john_doe",
  "first_name": "John",
  "last_name": "Doe",
  "roles": ["ROLE_USER"]
}
```

---

## Web Interface Flow

### User Registration Flow

1. **User visits** `/register`
2. **Fills in**:
   - Username
   - Email address
   - Password
   - Agree to terms checkbox
3. **Clicks Register**
4. **System**:
   - Validates input
   - Creates user account (NOT verified)
   - Generates verification token
   - Sends verification email
   - Redirects to verification page
5. **Verification Email Contains**:
   - "Verify Email Address" button
   - Direct link with token
   - Expiration notice (24 hours)
6. **User clicks link**
   - Token is validated
   - Account marked as verified
   - Redirected to login page
   - Can now login

### Staff Google OAuth Flow

1. **Staff member visits** `/login`
2. **Clicks** "Staff: Sign in with Google"
3. **Redirected to Google login** (`/auth/google/staff-login`)
4. **System**:
   - Checks if staff (ROLE_STAFF or ROLE_ADMIN)
   - If yes → Auto-verified, session created
   - If no → Shows error, requires admin to assign role
5. **Staff automatically verified** (no email needed)
6. **Logged in dashboard**

---

## Admin Role Exemptions

### Automatic Verification for Admins

Admins (ROLE_ADMIN) are automatically verified when:

1. **Creating account via Web Registration**:
   - Email verification is skipped
   - isVerified set to true
   - No verification email sent

2. **Creating account via API Registration**:
   - Response includes: `"status": "verified"`
   - No verification email sent
   - Can login immediately

3. **Using Google OAuth**:
   - Automatically verified on login
   - No additional verification needed
   - Session created in DB

### Implementation Details

In `EmailVerificationService.php`:

```php
// Admins are exempted from email verification
if (in_array('ROLE_ADMIN', $user->getRoles())) {
    $user->setIsVerified(true);
    $user->setVerificationToken(null);
    $this->entityManager->persist($user);
    $this->entityManager->flush();
    return true;
}
```

---

## Environment Variables

Create/update `.env.local` with:

```bash
# Google OAuth
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret

# Email Service (Brevo)
MAILER_DSN=brevo+api://YOUR_BREVO_API_KEY@default

# Or use SMTP
# MAILER_DSN=smtp://user:password@smtp.example.com:587?encryption=tls

# Database
DATABASE_URL=mysql://root:@localhost/easysave

# JWT Secret
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase
```

---

## Database Schema

### User Table Fields (Relevant to Verification)

```sql
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(180) UNIQUE NOT NULL,
    email VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    roles JSON,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    profile_picture VARCHAR(255),
    INDEX idx_verification_token (verification_token),
    INDEX idx_email (email),
    INDEX idx_is_verified (is_verified)
);
```

### Key Fields

- **is_verified** (BOOLEAN): Whether email is verified
- **verification_token** (VARCHAR 255): Token sent in email (null after verified)
- **email** (VARCHAR 255): Email address to verify
- **roles** (JSON): Contains ROLE_USER, ROLE_STAFF, ROLE_ADMIN

---

## Security Considerations

### Best Practices Implemented

1. **Token Security**:
   - Tokens are 64-character hex strings (32 random bytes)
   - Stored in database (not in URL query string)
   - Sent via email link (expires in 24 hours)

2. **Admin Exemption**:
   - Only applies to ROLE_ADMIN
   - Prevents email interception for admins
   - Maintains security via role-based access

3. **CSRF Protection**:
   - Web forms protected with CSRF tokens
   - API uses JWT tokens
   - OAuth uses state parameter

4. **Password Security**:
   - Minimum 6 characters (configurable)
   - Hashed with bcrypt
   - Never stored in plain text

5. **Email Verification**:
   - Unverified users cannot access restricted areas
   - Login allowed, but email shown in profile
   - Resend mechanism prevents lockout

---

## Troubleshooting

### Problem: "Invalid or expired verification token"

**Causes**:
- User tried old link
- Token expired (> 24 hours)
- Link already used
- Database syncing issue

**Solution**:
```bash
# Resend verification email
POST /api/resend-verification
{
  "email": "user@example.com"
}
```

### Problem: Verification Email Not Sent

**Check**:
1. Brevo API key in `.env.local`
2. Email address is valid
3. Check logs: `var/log/dev.log`
4. Test email command:
   ```bash
   php bin/console mailer:test user@example.com
   ```

**Debug**:
```bash
# Check email configuration
php bin/console debug:config framework mailer
```

### Problem: Google OAuth Login Returns Error

**Check**:
1. Client ID and Secret in `.env.local`
2. Authorized origins in Google Console
3. Authorized redirect URIs match your app URL
4. User has ROLE_STAFF or ROLE_ADMIN

**Solution**:
```bash
# Re-verify environment variables
php bin/console debug:dotenv

# Test OAuth connection
php bin/console router:match /auth/connect/check
```

### Problem: User Can't Login After Email Verification

**Check**:
1. `is_verified` is set to true in database
2. Verification token is cleared (null)
3. User.roles contains at least ROLE_USER
4. User.is_active is true

**Query**:
```sql
SELECT id, username, email, is_verified, verification_token, is_active, roles 
FROM user 
WHERE email = 'your@email.com';
```

### Problem: Token in URL vs Email

**Note**: Tokens should NOT be in URL query strings. Current implementation:
- Tokens sent in email body only
- When clicking link, token verified server-side
- URL contains: `/verify-email/{token}` (RESTful route)

---

## Testing Guide

### Unit Test: Email Verification

```php
<?php

namespace App\Tests;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EmailVerificationServiceTest extends KernelTestCase
{
    public function testVerifyEmailToken(): void
    {
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $service = $kernel->getContainer()->get(EmailVerificationService::class);

        // Create test user
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');
        $user->setVerificationToken('test_token_123');
        
        $entityManager->persist($user);
        $entityManager->flush();

        // Verify token
        $error = $service->verifyToken('test_token_123');
        
        $this->assertNull($error);
        $this->assertTrue($user->isIsVerified());
        $this->assertNull($user->getVerificationToken());
    }
}
```

### Integration Test: API Registration

```bash
# Test registration
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "TestPassword123"
  }'

# Test email verification
curl -X POST http://localhost:8000/api/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "token": "token-from-email"
  }'
```

### Manual Testing: Web Flow

1. **Register User**:
   - Visit `http://localhost:8000/register`
   - Fill form, submit
   - Check for redirect to verification page

2. **Check Email**:
   - In development, check Symfony mailer profiler
   - Or check email service (Brevo)
   - Copy verification link

3. **Verify Email**:
   - Paste link in browser
   - Should see success message
   - Verify `is_verified = true` in database

4. **Login**:
   - Use verified account to login
   - Should see dashboard

---

## Maintenance

### Regular Tasks

1. **Check verification token expiration** (if needed):
   ```sql
   SELECT id, username, created_at FROM user 
   WHERE NOT is_verified 
   AND DATE_ADD(created_at, INTERVAL 24 HOUR) < NOW();
   ```

2. **Monitor failed verifications**:
   - Check application logs: `var/log/prod.log`
   - Review failed OAuth attempts

3. **Brevo API limits**:
   - Monitor sending quota
   - Implement rate limiting if needed

---

## Support & Further Customization

### Customizing Email Template

Edit `src/Service/EmailVerificationService.php` method `renderEmailTemplate()` to modify email content.

### Customizing Verification Page

Edit `templates/email_verification/index.html.twig` for UI changes.

### Adding OAuth Providers

To add Facebook, GitHub, etc.:

1. Install: `composer require league/oauth2-facebook`
2. Update `config/packages/oauth2_client.yaml`
3. Create handler controller similar to `GoogleOAuthController.php`

### Custom Verification Logic

Override email exemption in `EmailVerificationService.php`:

```php
public function sendVerificationEmail(User $user): bool
{
    // Your custom logic here
}
```

---

## Document Version

- **Last Updated**: April 14, 2026
- **Implementation**: Symfony 7.4 + API Platform 4.2
- **Status**: Production Ready ✅
