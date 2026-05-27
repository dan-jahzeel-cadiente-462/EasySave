# 🧪 Mobile App Email Verification - Test Guide

**Purpose:** Complete testing guide for mobile app email verification flow  
**Status:** Ready for testing  
**Last Updated:** May 19, 2026

---

## Quick Test (5 Minutes)

### Prerequisite: Email Service Configured

Ensure `.env.local` has email configured (see [EMAIL_SETUP_GUIDE.md](EMAIL_SETUP_GUIDE.md)):

```env
# Option 1: Brevo (Production)
MAILER_DSN=brevo+api://YOUR_API_KEY@default

# Option 2: MailHog (Development)
MAILER_DSN=smtp://localhost:1025

# Option 3: Gmail
MAILER_DSN=smtp://your-email@gmail.com:app-password@smtp.gmail.com:587?encryption=tls
```

---

## Test Scenario 1: Complete Flow (Email Delivery)

### 1.1 Register New User

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "plainPassword": "Test@12345",
    "agreeTerms": true
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "test@example.com",
    "isVerified": false
  }
}
```

✅ **Status:** User registered, token received

### 1.2 Check Email Received

**For Brevo:**
1. Login to https://www.brevo.com/
2. Go to Transactional Emails
3. Look for email to `test@example.com`

**For MailHog:**
1. Visit http://localhost:8025
2. Find email from noreply@easysave.local

**For Gmail:**
1. Check inbox for email from noreply@easysave.local
2. Check spam folder if not in inbox

✅ **Status:** Email received with verification link

### 1.3 Extract Token from Email

Email contains:
```
Click here to verify: https://easysave.local/verify-email/abc123xyz...
```

Extract token: `abc123xyz...`

### 1.4 Verify Email via API

```bash
curl -X POST http://localhost:8000/api/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "token": "abc123xyz..."
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Email verified successfully",
  "code": "VERIFIED",
  "user": {
    "id": 1,
    "username": "testuser",
    "email": "test@example.com",
    "isVerified": true,
    "roles": ["ROLE_USER"]
  }
}
```

✅ **Status:** Email verified

### 1.5 Check Verification Status

```bash
curl -X GET http://localhost:8000/api/verification-status \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Verification status retrieved",
  "data": {
    "email": "test@example.com",
    "isVerified": true,
    "isExempt": false,
    "roles": ["ROLE_USER"]
  }
}
```

✅ **Status:** Verification confirmed in system

### 1.6 Login with Verified Account

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "Test@12345"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "test@example.com",
    "isVerified": true
  }
}
```

✅ **Status:** Login successful after verification

---

## Test Scenario 2: Development Mode (No Email Delivery)

For testing without email service running.

### 2.1 Register User

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "devuser",
    "email": "dev@test.local",
    "plainPassword": "Test@12345",
    "agreeTerms": true
  }'
```

### 2.2 Get Verification Token (Dev Endpoint)

```bash
curl -X GET "http://localhost:8000/api/dev/verification-token?email=dev@test.local"
```

**Expected Response:**
```json
{
  "success": true,
  "code": "DEV_TOKEN_RETRIEVED",
  "data": {
    "email": "dev@test.local",
    "verificationToken": "abc123xyz...",
    "verificationUrl": "http://localhost:8000/verify-email/abc123xyz...",
    "apiVerifyUrl": "/api/verify-email"
  }
}
```

✅ **Status:** Token retrieved directly (dev mode only)

### 2.3 Verify Using Dev Token

```bash
curl -X POST http://localhost:8000/api/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "token": "abc123xyz..."
  }'
```

**Expected Response:** Same as 1.4 - Verified

✅ **Status:** Email verified using dev endpoint

---

## Test Scenario 3: Error Handling

### 3.1 Invalid Token

```bash
curl -X POST http://localhost:8000/api/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "token": "invalid-token-12345"
  }'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Invalid or expired verification token",
  "code": "INVALID_TOKEN"
}
```

✅ **Status:** Proper error handling

### 3.2 Already Verified

Register, verify, then try to verify again:

```bash
curl -X POST http://localhost:8000/api/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "token": "already-used-token"
  }'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Invalid or expired verification token",
  "code": "INVALID_TOKEN"
}
```

✅ **Status:** Token invalidated after use

### 3.3 Resend Without Auth

```bash
curl -X POST http://localhost:8000/api/resend-verification
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Authentication required",
  "code": "UNAUTHORIZED"
}
```

✅ **Status:** Auth required

### 3.4 Resend for Verified User

Register, verify, then resend:

```bash
curl -X POST http://localhost:8000/api/resend-verification \
  -H "Authorization: Bearer JWT_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Email is already verified",
  "code": "ALREADY_VERIFIED"
}
```

✅ **Status:** Prevents unnecessary emails

---

## Test Scenario 4: Role-Based Exemptions

### 4.1 Staff User (No Verification Required)

In database, create user with `ROLE_STAFF`:
```sql
INSERT INTO user (username, email, roles, password, is_active, is_verified) 
VALUES ('staffuser', 'staff@example.com', '["ROLE_STAFF"]', 'hashed_pwd', true, false);
```

Try to login as unverified staff:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "staffuser",
    "password": "password"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 2,
    "email": "staff@example.com",
    "roles": ["ROLE_STAFF"],
    "isVerified": false
  }
}
```

✅ **Status:** Staff can login without verification

### 4.2 Admin User (No Verification Required)

Similar to 4.1 but with `ROLE_ADMIN`

✅ **Status:** Admin can login without verification

### 4.3 Regular User (Verification Required)

Regular user unverified:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "password": "Test@12345"
  }'
```

**Expected Response (if not verified):**
```json
{
  "success": false,
  "message": "Please verify your email before logging in. Check your inbox for the verification link.",
  "code": "VERIFICATION_REQUIRED"
}
```

✅ **Status:** Regular users must verify

---

## Test Scenario 5: Deep Linking

### 5.1 Click Email Link Directly

Email contains: `https://easysave.local/verify-email/abc123xyz...`

Click link in mobile app → Should verify automatically

### 5.2 Extract Token and Verify Programmatically

Get token from email → Use `POST /api/verify-email` → User verified

### 5.3 Resend and Test New Token

```bash
curl -X POST http://localhost:8000/api/resend-verification \
  -H "Authorization: Bearer JWT_TOKEN"
```

Old token becomes invalid, new token sent

✅ **Status:** Deep linking and token expiration working

---

## Automated Test Script

```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api"
EMAIL="autotest_$(date +%s)@test.local"
USERNAME="autotest_$(date +%s)"
PASSWORD="Test@12345"

echo "=== Email Verification Test Suite ==="
echo ""

# 1. Register
echo "1️⃣  Registering user..."
REGISTER=$(curl -s -X POST $BASE_URL/register \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$USERNAME\",
    \"email\": \"$EMAIL\",
    \"plainPassword\": \"$PASSWORD\",
    \"agreeTerms\": true
  }")

echo "Response: $REGISTER"
JWT_TOKEN=$(echo $REGISTER | jq -r '.token')
echo "✅ JWT Token: $JWT_TOKEN"
echo ""

# 2. Get Verification Token (Dev)
echo "2️⃣  Getting verification token (dev mode)..."
DEV_TOKEN=$(curl -s "http://localhost:8000/api/dev/verification-token?email=$EMAIL" \
  | jq -r '.data.verificationToken')

echo "✅ Verification Token: $DEV_TOKEN"
echo ""

# 3. Verify Email
echo "3️⃣  Verifying email..."
VERIFY=$(curl -s -X POST $BASE_URL/verify-email \
  -H "Content-Type: application/json" \
  -d "{\"token\": \"$DEV_TOKEN\"}")

echo "Response: $VERIFY"
echo ""

# 4. Check Status
echo "4️⃣  Checking verification status..."
STATUS=$(curl -s -X GET $BASE_URL/verification-status \
  -H "Authorization: Bearer $JWT_TOKEN")

IS_VERIFIED=$(echo $STATUS | jq -r '.data.isVerified')
echo "✅ Is Verified: $IS_VERIFIED"
echo ""

# 5. Login
echo "5️⃣  Testing login..."
LOGIN=$(curl -s -X POST $BASE_URL/login \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$USERNAME\",
    \"password\": \"$PASSWORD\"
  }")

echo "Response: $LOGIN"
LOGIN_SUCCESS=$(echo $LOGIN | jq -r '.success')

if [ "$LOGIN_SUCCESS" == "true" ]; then
  echo "✅ Login successful after verification!"
else
  echo "❌ Login failed"
fi

echo ""
echo "=== Test Complete ==="
```

Run with:
```bash
bash test_mobile_verification.sh
```

---

## Postman Collection

Import `postman_mobile_api_collection.json` into Postman to test all endpoints:

1. Open Postman
2. File → Import
3. Select `postman_mobile_api_collection.json`
4. Set environment variables:
   - `base_url`: http://localhost:8000/api
   - `jwt_token`: (obtained from login/register)
   - `email`: test@example.com
5. Run requests in order

---

## Checklist - Ready for Mobile Development

- [ ] Email service configured in `.env.local`
- [ ] Cache cleared: `php bin/console cache:clear`
- [ ] Test Scenario 1 (Complete Flow) passes
- [ ] Test Scenario 2 (Dev Mode) passes
- [ ] Test Scenario 3 (Error Handling) passes
- [ ] Test Scenario 4 (Role-Based) passes
- [ ] Test Scenario 5 (Deep Linking) passes
- [ ] Staff/Admin exemptions working
- [ ] Regular users required to verify
- [ ] Mobile app integration documented
- [ ] Error codes understood by mobile team
- [ ] Deep linking configured in mobile app

---

## Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| 404 for /api/dev//* endpoints | Not in dev mode | Set `APP_ENV=dev` in `.env.local` |
| Dev endpoints return 403 | Production mode | Ensure `APP_ENV=dev` |
| Email not received | Service not configured | Setup email in `.env.local` |
| Token invalid | Already used/expired | Call resend to get new token |
| "Verification required" error | User not verified | Call verify endpoint with token |
| Staff can't login | Exemption not working | Check `ROLE_STAFF` in database |
| CORS errors | Mobile app origin not allowed | Add to CORS_ALLOW_ORIGIN in `.env` |

---

## Next Steps

1. ✅ Setup email service (see [EMAIL_SETUP_GUIDE.md](EMAIL_SETUP_GUIDE.md))
2. ✅ Run test scenarios above
3. ✅ Integrate into mobile app using examples in [MOBILE_APP_VERIFICATION_GUIDE.md](MOBILE_APP_VERIFICATION_GUIDE.md)
4. ✅ Configure deep linking in mobile app
5. ✅ Test end-to-end in staging
6. ✅ Deploy to production

