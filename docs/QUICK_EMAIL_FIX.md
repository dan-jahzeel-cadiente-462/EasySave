# QUICK FIX: Email Not Sending

## The Problem
```
✗ User sees: "A verification link has been sent to dcadiente463@gmail.com"
✗ Reality: Email never actually sent (MAILER_DSN=native://default doesn't work)
```

## The Root Cause
**File:** `.env` (line 65)
```
MAILER_DSN=native://default  # Uses PHP mail() - not configured on XAMPP
```

---

## QUICK FIX (5 minutes)

### Step 1: Create `.env.local` file

**Location:** `EasySave/.env.local` (root directory, same level as `.env`)

### Step 2: Choose Your Email Service

#### Option 1: Brevo (Recommended) ⭐
Copy this into `.env.local`:
```env
###> symfony/brevo-mailer ###
MAILER_DSN=brevo+api://YOUR_BREVO_API_KEY@default
###< symfony/brevo-mailer ###
```

**Get your API key:**
1. Go to https://www.brevo.com/
2. Sign up or login
3. Settings → API & Webhooks → Copy v3 API Key
4. Replace `YOUR_BREVO_API_KEY` with your actual key

---

#### Option 2: Gmail SMTP
Copy this into `.env.local`:
```env
###> symfony/mailer ###
MAILER_DSN=smtp://YOUR_EMAIL@gmail.com:YOUR_APP_PASSWORD@smtp.gmail.com:587?encryption=tls
MAILER_FROM_ADDRESS=noreply@easysave.local
MAILER_FROM_NAME="EasySave"
###< symfony/mailer ###
```

**Setup:**
1. Enable 2FA on Gmail: https://myaccount.google.com/security
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Replace `YOUR_EMAIL` and `YOUR_APP_PASSWORD` with your values

---

#### Option 3: MailHog (Local Testing - No Setup Needed After Install) 
Copy this into `.env.local`:
```env
###> symfony/mailer ###
MAILER_DSN=smtp://localhost:1025
MAILER_FROM_ADDRESS=noreply@easysave.local
MAILER_FROM_NAME="EasySave"
###< symfony/mailer ###
```

**Setup:**
1. Download MailHog: https://github.com/mailhog/MailHog/releases
2. Run the executable
3. Visit http://localhost:8025 to see test emails

---

## Step 3: Verify It's Working

Check Symfony recognizes the new config:
```bash
php bin/console debug:config framework.mailer
```

You should see your email DSN (not `native://default`).

---

## Test It

1. Register a new user with an email
2. Check:
   - **Brevo:** Check Brevo dashboard for sent emails
   - **Gmail:** Check inbox/spam folder
   - **MailHog:** Visit http://localhost:8025

---

## File Structure After Fix
```
EasySave/
├── .env                    (has native://default - OLD)
├── .env.local             (NEW - has YOUR_BREVO_API_KEY)
├── .env.oauth.example
└── ...
```

**.env.local is already in .gitignore** (so API keys are safe)

---

## Common Issues

**Issue:** Still showing "native://default"
- **Fix:** Make sure `.env.local` is in the ROOT directory
- **Check:** File path should be `EasySave/.env.local` not `EasySave/config/.env.local`

**Issue:** "Invalid API key" error
- **Fix:** Copy the API key exactly from Brevo (no extra spaces)
- **Check:** Visit Brevo dashboard to verify the key

**Issue:** "Connection refused"  
- **Fix 1 (Brevo):** Check internet connection, Brevo API might be down
- **Fix 2 (Gmail):** Verify Gmail App Password is correct
- **Fix 3 (MailHog):** Make sure MailHog is running on localhost:1025

**Issue:** Email sent but not received
- **Gmail:** Check spam/promotions folder
- **Brevo:** Check Brevo dashboard bounce logs
- **MailHog:** Verify mail caught in MailHog UI

---

## Current Error Flow

When `MAILER_DSN=native://default`:
```
User clicks Register
    ↓
Form submitted with email
    ↓
User saved to database
    ↓
Verification email "sent" (actually failed silently)
    ↓
Success message shown to user ← 🚨 PROBLEM: User thinks email sent!
    ↓
User never receives email
    ↓
User can't verify account
```

---

## After Fix Error Flow

```
User clicks Register
    ↓
Form submitted with email
    ↓
User saved to database
    ↓
Verification email sent via Brevo/Gmail/MailHog ← ✅ WORKS
    ↓
Success message shown
    ↓
User receives email
    ↓
User clicks link to verify
    ↓
Account verified, can login
```

---

## Need Help?

- **Brevo Setup:** https://www.brevo.com/
- **Gmail App Passwords:** https://myaccount.google.com/apppasswords
- **MailHog Downloads:** https://github.com/mailhog/MailHog/releases
- **Symfony Mailer Docs:** https://symfony.com/doc/current/mailer.html

