# Email Configuration Diagnostic Guide

## Current System Status

**Problem:** "Verification link sent" message appears but email is not received.

**Root Cause:** `MAILER_DSN=native://default` in `.env` uses PHP's mail() function which doesn't work on XAMPP.

---

## How to Diagnose

### Step 1: Check Current Mailer Configuration

```bash
php bin/console config:dump framework.mailer
```

**Expected Output (BROKEN):**
```yaml
framework:
  mailer:
    dsn: 'native://default'  # ❌ This doesn't work
```

**Expected Output (FIXED):**
```yaml
framework:
  mailer:
    dsn: 'brevo+api://YOUR_API_KEY@default'  # ✅ This works
```

---

### Step 2: Check for `.env.local` File

The fix requires a `.env.local` file in the project root:

```bash
# Check if .env.local exists
ls -la .env.local

# If it doesn't exist, create it:
touch .env.local
```

---

### Step 3: Verify File Permissions

```bash
# Check that .env.local is readable
ls -la .env.local

# Should show something like:
# -rw-r--r-- ... .env.local

# If not readable, fix permissions:
chmod 644 .env.local
```

---

### Step 4: Check Symfony Logs

```bash
# Follow real-time logs
tail -f var/log/dev.log

# Or view last 50 lines
tail -50 var/log/dev.log
```

**Look for lines like:**
```
[2026-05-19 10:30:45] app.INFO: Starting email verification send...
[2026-05-19 10:30:46] app.INFO: Verification email sent successfully
```

**Or error lines:**
```
[2026-05-19 10:30:45] app.ERROR: Failed to send verification email
```

---

### Step 5: Test Email Sending Manually

```bash
php bin/console debug:config framework.mailer

# If that works, try actual send with:
php bin/console make:command TestEmailCommand
# Then add email sending code
```

---

## Quick Diagnostic Checklist

- [ ] `.env.local` file exists in project root
- [ ] `.env.local` contains `MAILER_DSN` with valid service
- [ ] `MAILER_DSN` does NOT contain `native://default`
- [ ] `.env.local` is not corrupted (valid syntax)
- [ ] File permissions allow reading
- [ ] Symfony cache cleared: `php bin/console cache:clear`
- [ ] Email service credentials are correct (Brevo API key, Gmail password, etc.)
- [ ] Firewall allows outbound connections to email service

---

## Configuration Files Involved

| File | Purpose | Current Status |
|------|---------|--------|
| `.env` | Default config (committed) | Has `native://default` |
| `.env.local` | Local overrides (NOT committed) | **NEEDS TO BE CREATED** |
| `config/packages/mailer.yaml` | Mailer configuration | Reads from `MAILER_DSN` env var |
| `src/Service/EmailVerificationService.php` | Email sending service | **Now has logging** ✅ |
| `src/Controller/RegistrationController.php` | Registration logic | **Enhanced error handling** ✅ |

---

## Log Messages After Fix

### Successful Send
```
[2026-05-19 10:30:45] app.INFO: Starting email verification send [{"user_id":1,"email":"dcadiente463@gmail.com","username":"dcadiente"}]
[2026-05-19 10:30:45] app.DEBUG: Email object created [{"from":"noreply@easysave.local","to":"dcadiente463@gmail.com","subject":"Verify Your Email Address"}]
[2026-05-19 10:30:46] app.INFO: Verification email sent successfully [{"user_id":1,"email":"dcadiente463@gmail.com"}]
```

### Failed Send (API Key Missing)
```
[2026-05-19 10:30:45] app.INFO: Starting email verification send [{"user_id":1,"email":"dcadiente463@gmail.com","username":"dcadiente"}]
[2026-05-19 10:30:45] app.ERROR: Failed to send verification email [{"user_id":1,"email":"dcadiente463@gmail.com","error":"Invalid Brevo API key","code":401}]
```

### Failed Send (No Config)
```
[2026-05-19 10:30:45] app.INFO: Starting email verification send [{"user_id":1,"email":"dcadiente463@gmail.com","username":"dcadiente"}]
[2026-05-19 10:30:45] app.ERROR: Failed to send verification email [{"user_id":1,"email":"dcadiente463@gmail.com","error":"The native mailer is not configured. Please install and configure a real mail provider.","code":0}]
```

---

## Environment Setup Examples

### For Brevo
`.env.local`:
```env
MAILER_DSN=brevo+api://abc123def456ghi789@default
MAILER_FROM_ADDRESS=noreply@easysave.local
MAILER_FROM_NAME="EasySave"
```

### For Gmail
`.env.local`:
```env
MAILER_DSN=smtp://user@gmail.com:abcd1234efgh5678ijkl@smtp.gmail.com:587?encryption=tls
MAILER_FROM_ADDRESS=noreply@easysave.local
MAILER_FROM_NAME="EasySave"
```

### For MailHog (Dev Only)
`.env.local`:
```env
MAILER_DSN=smtp://localhost:1025
MAILER_FROM_ADDRESS=noreply@easysave.local
MAILER_FROM_NAME="EasySave"
```

---

## Testing the Fix

After creating `.env.local`:

### 1. Clear Cache
```bash
php bin/console cache:clear
```

### 2. Verify Configuration Loaded
```bash
php bin/console debug:config framework.mailer
```

### 3. Test Registration
1. Go to registration page
2. Register with a test email
3. Check logs: `tail -f var/log/dev.log`
4. Check email inbox (or Brevo/MailHog dashboard)

### 4. Verify Email Received
- **Brevo:** Check https://www.brevo.com/ → Transactional Emails
- **Gmail:** Check inbox and spam folder
- **MailHog:** Visit http://localhost:8025

---

## Common Error Messages & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| `native mailer is not configured` | `MAILER_DSN=native://default` | Create `.env.local` with real provider |
| `Invalid Brevo API key` | Wrong API key in .env.local | Verify key from Brevo dashboard |
| `Connection refused` | Email service unreachable | Check internet, firewall, service status |
| `SMTP auth failed` | Wrong Gmail password | Use App Password, not regular password |
| `/.env.local not found` | File in wrong location | Must be in project root (same as .env) |

---

## Advanced Debugging

### View Email Queue (if using async transport)
```bash
php bin/console messenger:consume async
```

### Check Mailer Transport Details
```bash
php bin/console debug:container mailer
```

### Dump Full Email Configuration
```bash
php bin/console debug:config framework.mailer -vv
```

---

## Files Modified in This Update (May 19, 2026)

1. ✅ `src/Service/EmailVerificationService.php`
   - Added LoggerInterface injection
   - Added detailed logging for email send process
   - Better error reporting

2. ✅ `src/Controller/RegistrationController.php`
   - Enhanced error messages to user
   - Better logging of failures

3. ✅ `src/Controller/EmailVerificationController.php`
   - Better error handling in resend logic
   - Improved error messages

4. ✅ `src/Controller/ApiEmailVerificationController.php`
   - Added try-catch for email sending
   - Better API error responses

---

## Next Steps

1. **Create `.env.local` with your email provider** (see QUICK_EMAIL_FIX.md)
2. **Clear cache:** `php bin/console cache:clear`
3. **Test registration:** Register with test email
4. **Check logs:** `tail -f var/log/dev.log`
5. **Verify email received** in your inbox or email service dashboard

