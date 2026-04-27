# Quick Testing Guide - Dual Login System

## Test Scenarios

### Scenario 1: Admin Login (Form-Based Only)
**URL:** `GET http://127.0.0.1:8000/admin/login`

**Steps:**
1. Navigate to admin login page
2. Enter admin credentials (username/password)
3. Click "Login"
4. Should redirect to `/admin/dashboard`

**Expected Behavior:**
- ✅ Form login works
- ✅ Google OAuth button NOT visible
- ✅ Email verification NOT required
- ✅ Only ROLE_ADMIN or ROLE_STAFF can login
- ✅ Link to staff/user login at bottom

---

### Scenario 2: Staff/User Login (Form-Based)
**URL:** `GET http://127.0.0.1:8000/login`

**Steps:**
1. Navigate to user login page
2. Enter staff/user credentials
3. Click "Login"
4. Should redirect to appropriate dashboard based on role

**Expected Behavior:**
- ✅ Form login works
- ✅ Google OAuth button visible
- ✅ Email verification enforced for regular users
- ✅ Email verification SKIPPED for staff/admin
- ✅ Link to admin login at bottom

---

### Scenario 3: Google OAuth Login
**URL:** `http://127.0.0.1:8000/login`

**Steps:**
1. Navigate to user login page
2. Click "Continue with Google"
3. Redirected to Google sign-in
4. Authenticate with Google account
5. Redirected back to app
6. Should auto-login and redirect to dashboard

**Expected Behavior:**
- ✅ Creates new user if doesn't exist
- ✅ Email automatically verified
- ✅ New users get ROLE_USER
- ✅ Existing users logged in directly
- ✅ No email verification prompt

---

### Scenario 4: Verify Admin Exemption
**Test User:** Admin account without verified email

**Steps:**
1. Create admin user without email verification
2. Try logging in via `/admin/login`
3. Should succeed

**Expected:** 
- ✅ Login succeeds (no email verification needed)
- ✅ Redirects to admin dashboard

---

### Scenario 5: Verify Regular User Email Check
**Test User:** Regular user without verified email

**Steps:**
1. Create regular user without email verification
2. Try logging in via `/login`
3. Should fail with message

**Expected:**
- ✅ Login fails
- ✅ Message: "Please verify your email before logging in..."
- ✅ Cannot proceed without email verification

---

### Scenario 6: Verify Staff Exemption
**Test User:** Staff account without verified email

**Steps:**
1. Create staff user without email verification
2. Try logging in via `/admin/login` or `/login`
3. Should succeed

**Expected:**
- ✅ Login succeeds (no email verification needed)

---

## Test Data Setup

### Admin User
```sql
INSERT INTO user (username, password, roles, email, first_name, last_name, is_active, is_verified, provider)
VALUES ('admin', '$2y$13$...hashed_password...', '["ROLE_ADMIN"]', 'admin@example.com', 'Admin', 'User', 1, 0, 'local');
```

### Staff User (No Email Verification Required)
```sql
INSERT INTO user (username, password, roles, email, first_name, last_name, is_active, is_verified, provider)
VALUES ('staff', '$2y$13$...hashed_password...', '["ROLE_STAFF"]', 'staff@example.com', 'Staff', 'Member', 1, 0, 'local');
```

### Regular User (Needs Email Verification)
```sql
INSERT INTO user (username, password, roles, email, first_name, last_name, is_active, is_verified, provider)
VALUES ('user', '$2y$13$...hashed_password...', '["ROLE_USER"]', 'user@example.com', 'Regular', 'User', 1, 0, 'local');
```

### Google User (Auto-Verified)
- Create via OAuth login in scenario 3
- Auto-verified via Google
- No further action needed

---

## Login URLs Summary

| Role | URL | Method | Email Required | OAuth Available |
|------|-----|--------|-----------------|-----------------|
| Admin | `/admin/login` | POST | ❌ No | ❌ No |
| Staff | `/login` | POST | ❌ No | ✅ Yes |
| User | `/login` | POST | ✅ Yes | ✅ Yes |
| Any | `/connect/google` | GET | N/A | ✅ Yes |

---

## Error Messages to Check

| Scenario | Expected Message |
|----------|-----------------|
| Admin without ROLE_ADMIN/STAFF on `/admin/login` | "Admin access required." |
| Regular user without email verification on `/login` | "Please verify your email before logging in. Check your inbox for the verification link." |
| Deactivated user on any login | "Your account has been deactivated. Please contact support." |
| Invalid credentials | "Invalid credentials." |

---

## Debug Commands

```bash
# Check all routes
php bin/console debug:router | grep login

# Clear cache
php bin/console cache:clear

# Check security configuration
php bin/console debug:config security

# Verify authenticators are loaded
php bin/console debug:container | grep authenticator
```

---

**Last Updated:** 2026-04-16
