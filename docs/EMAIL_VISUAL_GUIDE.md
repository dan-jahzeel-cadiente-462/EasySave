# 🔧 EMAIL FIX - VISUAL REFERENCE

## The Problem (Current State ❌)

```
┌─────────────────────────────────┐
│  User Registers                 │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  Form submitted with email      │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  User saved to database         │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  MAILER_DSN=native://default    │ ← Problem here!
│  (PHP mail() - not configured)  │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  Email send fails silently      │
│  ❌ No error shown to server    │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  Success message to user:       │
│  "Verification link sent!"      │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  User checks email inbox        │
│  ❌ NO EMAIL FOUND             │
│  User is confused!              │
└─────────────────────────────────┘
```

---

## The Solution (After Fix ✅)

```
┌─────────────────────────────────┐
│  User Registers                 │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  Form submitted with email      │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  User saved to database         │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  MAILER_DSN from .env.local    │
│  (Configured provider)          │ ← Fixed!
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  Email sent via:                │
│  ✅ Brevo API, OR              │
│  ✅ Gmail SMTP, OR             │
│  ✅ MailHog                    │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  Success logged:                │
│  "Verification email sent"      │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  User receives email with link  │
│  ✅ EMAIL DELIVERED            │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  User clicks verification link  │
└──────────────┬──────────────────┘
               ↓
┌─────────────────────────────────┐
│  Account verified               │
│  User can now login             │
│  ✅ SUCCESS                    │
└─────────────────────────────────┘
```

---

## Quick Fix (Copy-Paste Ready)

### For Brevo Users:

**Step 1:** Create file `EasySave/.env.local`

**Step 2:** Paste this:
```env
###> symfony/brevo-mailer ###
MAILER_DSN=brevo+api://YOUR_API_KEY_HERE@default
###< symfony/brevo-mailer ###
```

**Step 3:** Replace `YOUR_API_KEY_HERE` with your Brevo API key

**Step 4:** Run this command:
```bash
php bin/console cache:clear
```

**Step 5:** Test by registering a new user

---

### For Gmail Users:

**Step 1:** Create file `EasySave/.env.local`

**Step 2:** Paste this:
```env
###> symfony/mailer ###
MAILER_DSN=smtp://YOUR_EMAIL@gmail.com:YOUR_APP_PASSWORD@smtp.gmail.com:587?encryption=tls
###< symfony/mailer ###
```

**Step 3:** Replace:
- `YOUR_EMAIL` with your Gmail address
- `YOUR_APP_PASSWORD` with 16-char app password from https://myaccount.google.com/apppasswords

**Step 4:** Run this command:
```bash
php bin/console cache:clear
```

**Step 5:** Test by registering a new user

---

### For Local Testing with MailHog:

**Step 1:** Download MailHog: https://github.com/mailhog/MailHog/releases

**Step 2:** Run MailHog executable (creates SMTP server on localhost:1025)

**Step 3:** Create file `EasySave/.env.local`

**Step 4:** Paste this:
```env
###> symfony/mailer ###
MAILER_DSN=smtp://localhost:1025
###< symfony/mailer ###
```

**Step 5:** Run this command:
```bash
php bin/console cache:clear
```

**Step 6:** Register test user and check http://localhost:8025 for email

---

## Configuration Comparison

### ❌ BROKEN (Current)
```
.env (committed to git)
───────────────────────
MAILER_DSN=native://default
```

**Result:** Email send fails silently (PHP mail() not configured)

---

### ✅ FIXED (After Setup)
```
.env (committed to git)
───────────────────────
MAILER_DSN=native://default

.env.local (NOT in git - local only)
─────────────────────────────────────
MAILER_DSN=brevo+api://KEY@default
        OR
MAILER_DSN=smtp://user@gmail.com:password@smtp.gmail.com:587?encryption=tls
        OR
MAILER_DSN=smtp://localhost:1025
```

**Result:** Email sends successfully ✅

---

## Enhanced Error Messages

### User Sees (Before Fix ❌)
```
⚠️  "Registration successful, but we could not 
     send a verification email. Please contact support."
```

### User Sees (After Fix ✅)
```
✅  "Registration successful! Please check your email 
    to verify your account."

    (Or if still fails:)

⚠️  "Registration successful! However, we could not 
    send the verification email. Please check: 
    1) Your email address is correct, 
    2) Check your spam folder, 
    3) Contact support if problems persist."
```

---

## Troubleshooting Chart

| Issue | Check | Fix |
|-------|-------|-----|
| Still using `native://default` | `.env.local` doesn't exist OR wrong location | Create `.env.local` in project root |
| "Invalid API key" error | Brevo API key incorrect | Copy exact key from Brevo dashboard |
| Email not received | Email provider not running | Start Brevo/Gmail/MailHog service |
| Gmail auth failed | Using regular password instead of app password | Use 16-char App Password from Google |
| MailHog not receiving | MailHog not running | Download and run MailHog executable |

---

## File Locations Reference

```
EasySave/
│
├── .env                           ← Default config (has native://default)
├── .env.local          🆕         ← CREATE THIS (has your email config)
├── .env.oauth.example             ← Reference template
│
├── config/
│   └── packages/
│       └── mailer.yaml            ← Reads MAILER_DSN from .env
│
├── src/
│   ├── Service/
│   │   └── EmailVerificationService.php    ← Enhanced with logging ✅
│   └── Controller/
│       ├── RegistrationController.php      ← Better errors ✅
│       ├── EmailVerificationController.php ← Better errors ✅
│       └── ApiEmailVerificationController.php ← Better errors ✅
│
├── var/
│   └── log/
│       └── dev.log                ← Check here for email logs
│
├── QUICK_EMAIL_FIX.md             ← 5-minute guide 📋
├── EMAIL_SETUP_GUIDE.md           ← Detailed guide 📖
├── EMAIL_DIAGNOSTIC_GUIDE.md      ← Diagnostics 🔍
└── EMAIL_FIX_SUMMARY.md           ← This fix summary 📝
```

---

## Verification Checklist

After creating `.env.local`:

```
[ ] File created: EasySave/.env.local
[ ] Contains MAILER_DSN (NOT native://default)
[ ] Contains valid email service credentials
[ ] Ran: php bin/console cache:clear
[ ] Registered test user with email
[ ] Check logs: tail -f var/log/dev.log
    Looking for: "Verification email sent successfully"
[ ] Email received in inbox or dashboard
[ ] Clicked verification link in email
[ ] Account is now verified
[ ] Can login successfully
[ ] Admin/Staff users can login without email verification ✓
```

---

## Log Examples

### ✅ Success Log
```
[2026-05-19 10:30:45] app.INFO: Starting email verification send 
  {"user_id":1,"email":"dcadiente463@gmail.com","username":"dcadiente"}

[2026-05-19 10:30:45] app.DEBUG: Email object created 
  {"from":"noreply@easysave.local","to":"dcadiente463@gmail.com",
   "subject":"Verify Your Email Address"}

[2026-05-19 10:30:46] app.INFO: Verification email sent successfully 
  {"user_id":1,"email":"dcadiente463@gmail.com"}
```

### ❌ Failure Log
```
[2026-05-19 10:30:45] app.INFO: Starting email verification send 
  {"user_id":1,"email":"dcadiente463@gmail.com","username":"dcadiente"}

[2026-05-19 10:30:46] app.ERROR: Failed to send verification email 
  {"user_id":1,"email":"dcadiente463@gmail.com",
   "error":"Invalid Brevo API key","code":401}
```

---

## Next Steps

1. **Pick Your Provider:**
   - Brevo (Production recommended)
   - Gmail (Free, easy setup)
   - MailHog (Dev testing)

2. **Create .env.local** with configuration from above

3. **Clear Cache:**
   ```bash
   php bin/console cache:clear
   ```

4. **Test Registration** with email

5. **Check Email Inbox** (or service dashboard)

6. **Done!** 🎉

---

## Questions?

See detailed guides:
- `QUICK_EMAIL_FIX.md` - Fast setup
- `EMAIL_SETUP_GUIDE.md` - Complete guide  
- `EMAIL_DIAGNOSTIC_GUIDE.md` - Troubleshooting

