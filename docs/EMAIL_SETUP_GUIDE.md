# Email Verification Fix - Setup Guide

## Problem
Verification link message is shown: *"A verification link has been sent to dcadiente463@gmail.com"*  
But **NO EMAIL IS RECEIVED** because the mailer is not configured properly.

---

## Current Configuration Issue

**File:** `.env`
```
MAILER_DSN=native://default  # ❌ This uses PHP mail() - doesn't work in development!
```

The `native://default` transport relies on PHP's `mail()` function, which typically:
- Doesn't work on local development machines
- Has no SMTP configuration
- Doesn't actually send emails anywhere

---

## Solution: Configure Brevo Email Service

### Step 1: Get Brevo API Key

1. Go to [https://www.brevo.com/](https://www.brevo.com/)
2. Sign up or log in to your account
3. Navigate to **Settings → API & Webhooks → SMTP & API**
4. Copy your **v3 API Key**

### Step 2: Create `.env.local` File

Create a new file: `.env.local` in the root directory (same level as `.env`)

Add your Brevo configuration:
```env
###> symfony/brevo-mailer ###
MAILER_DSN=brevo+api://YOUR_BREVO_API_KEY@default
###< symfony/brevo-mailer ###

###> symfony/mailer ###
MAILER_FROM_ADDRESS=noreply@easysave.local
MAILER_FROM_NAME="EasySave"
###< symfony/mailer ###
```

**Replace `YOUR_BREVO_API_KEY` with your actual API key from Brevo.**

### Step 3: Verify Configuration

Check that the configuration is loaded:
```bash
php bin/console config:dump framework.mailer
```

You should see your Brevo DSN listed.

### Step 4: Test Email Sending

```bash
php bin/console debug:config framework mailer
```

---

## Alternative Email Providers

If you don't want to use Brevo, use one of these alternatives:

### Option A: Gmail SMTP
```env
MAILER_DSN=smtp://YOUR_EMAIL@gmail.com:YOUR_APP_PASSWORD@smtp.gmail.com:587?encryption=tls
```

**Setup Gmail:**
1. Enable 2-Factor Authentication
2. Generate an "App Password" at https://myaccount.google.com/apppasswords
3. Use that app password (16 characters, no spaces)

### Option B: MailHog (Local Testing)
Perfect for local development without external services:

```env
MAILER_DSN=smtp://localhost:1025
```

**Setup MailHog:**
1. Download: https://github.com/mailhog/MailHog/releases
2. Run the executable
3. Web UI: http://localhost:8025 (see all test emails)

### Option C: SendGrid
```env
MAILER_DSN=smtp://apikey:SG.YOUR_API_KEY@smtp.sendgrid.net:587?encryption=tls
```

### Option D: Mailgun
```env
MAILER_DSN=smtp://postmaster@sandboxXXX.mailgun.org:YOUR_PASSWORD@smtp.mailgun.org:587?encryption=tls
```

---

## File Structure

After setup, your root directory should contain:
```
EasySave/
├── .env                    (committed - has native://default)
├── .env.local             (NEW - not committed - has Brevo key)
├── .env.oauth.example     (reference)
├── config/
│   └── packages/
│       └── mailer.yaml    (reads MAILER_DSN from .env)
├── src/
│   └── Service/
│       └── EmailVerificationService.php
└── ...
```

**Important:** `.env.local` should be in `.gitignore` (it is by default) to keep API keys secure.

---

## How Email Sending Works

### Without Configuration (Current State ❌)
```
User Registration
    ↓
Generate verification token
    ↓
Try to send email via native://default
    ↓
PHP mail() function (not configured)
    ↓
❌ EMAIL LOST - User sees success message but never gets email
```

### With Brevo Configuration (After Fix ✅)
```
User Registration
    ↓
Generate verification token
    ↓
Send email via Brevo API
    ↓
Brevo servers receive request
    ↓
Email sent to user's inbox (Gmail, Outlook, etc.)
    ↓
✅ EMAIL DELIVERED - User receives verification link
```

---

## Email Content Being Sent

**From:** noreply@easysave.local  
**Subject:** Verify Your Email Address  
**Content:** Professional HTML email with:
- Welcome message
- Clickable "Verify Email" button
- Direct link to verification URL
- 24-hour expiration notice

---

## Verification Process After Setup

1. ✅ User registers with email
2. ✅ Brevo receives send request
3. ✅ Email delivered to inbox
4. ✅ User clicks verification link
5. ✅ Token validated in database
6. ✅ User marked as verified
7. ✅ User can now login

---

## Troubleshooting

### Email Still Not Sending?

**Check 1: Verify DSN is loaded**
```bash
php bin/console debug:config framework.mailer
```

**Check 2: Check logs**
```bash
tail -f var/log/dev.log
```

Look for mailer errors.

**Check 3: Test send manually**
```bash
php bin/console make:migration
# Then check if any migration errors occur
```

**Check 4: Verify Brevo API key**
- Log into Brevo dashboard
- Confirm API key is correct (no typos)
- Ensure API key has permission to send emails

### Still Getting "native://default"?

Make sure `.env.local` is in the root directory, not in a subdirectory:
```
✅ Correct:  EasySave/.env.local
❌ Wrong:    EasySave/config/.env.local
```

---

## Production Considerations

- ✅ Use Brevo API key (more reliable)
- ✅ Store API key in `.env.local` (never commit)
- ✅ Use `MAILER_FROM_ADDRESS` with real domain
- ✅ Monitor Brevo dashboard for delivery status
- ✅ Set up bounce/complaint handling

---

## Security Notes

- Never commit `.env.local` with API keys
- `.env.local` is already in `.gitignore`
- Rotate API keys periodically
- Use environment-specific keys for dev/prod

