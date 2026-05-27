# 📱 Mobile App Email Verification Guide

**Updated:** May 19, 2026  
**Status:** ✅ Ready for Mobile App Development

---

## Overview

The EasySave API now provides complete email verification support for mobile app development, including:

- ✅ Standard email verification endpoints (production)
- ✅ Development/testing endpoints (local testing)
- ✅ Deep linking support for mobile apps
- ✅ Comprehensive error codes for better UX
- ✅ Admin/Staff role exemptions

---

## Quick Start - Mobile App Flow

### Step 1: User Registers (via API)

**Request:**
```bash
POST /api/register
Content-Type: application/json

{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "securepass123",
  "plainPassword": "securepass123",
  "agreeTerms": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "john@example.com",
    "isVerified": false
  }
}
```

### Step 2: Email Sent to User

Backend sends verification email to `john@example.com` containing:
```
Click here to verify: https://easysave.local/verify-email/abc123xyz...
```

### Step 3: User Opens Email in Mobile App

**Option A: Click Email Link**
- Mobile app opens deep link
- User redirected to verification page
- Account verified automatically

**Option B: Copy Token from Email**
- User extracts token: `abc123xyz...`
- Call API endpoint to verify

**Request:**
```bash
POST /api/verify-email
Content-Type: application/json

{
  "token": "abc123xyz..."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Email verified successfully",
  "code": "VERIFIED",
  "user": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "isVerified": true,
    "roles": ["ROLE_USER"]
  }
}
```

### Step 4: User Can Now Login

**Request:**
```bash
POST /api/login
Content-Type: application/json

{
  "username": "john_doe",
  "password": "securepass123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "john@example.com",
    "isVerified": true
  }
}
```

---

## API Endpoints

### 1. Verify Email (Production)

**POST** `/api/verify-email`

Verifies user email with token. Call this after user clicks email link or manually enters token.

**Request:**
```json
{
  "token": "64-character-hex-token"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Email verified successfully",
  "code": "VERIFIED",
  "user": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "isVerified": true,
    "roles": ["ROLE_USER"]
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Invalid or expired verification token",
  "code": "INVALID_TOKEN"
}
```

**Error Codes:**
- `MISSING_TOKEN` (400) - No token provided
- `INVALID_TOKEN` (400) - Token not found or expired
- `VERIFICATION_ERROR` (500) - Server error

---

### 2. Resend Verification Email (Production)

**POST** `/api/resend-verification`

Resends verification email to authenticated user. Requires JWT token.

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Verification email sent successfully",
  "code": "EMAIL_SENT",
  "data": {
    "email": "john@example.com",
    "tokenExpiration": "24 hours",
    "deepLink": "https://easysave.local/verify-email/new-token-here"
  }
}
```

**Error Codes:**
- `UNAUTHORIZED` (401) - Not authenticated
- `EXEMPT_FROM_VERIFICATION` (400) - Admin/Staff account
- `ALREADY_VERIFIED` (400) - Email already verified
- `EMAIL_SEND_FAILED` (500) - Email service error

---

### 3. Check Verification Status (Production)

**GET** `/api/verification-status`

Get current user's verification status. Requires JWT token.

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "Verification status retrieved",
  "code": "STATUS_OK",
  "data": {
    "userId": 1,
    "email": "john@example.com",
    "isVerified": true,
    "isExempt": false,
    "exemptReason": null,
    "roles": ["ROLE_USER"]
  }
}
```

---

## Development/Testing Endpoints (DEV MODE ONLY)

These endpoints **only work in development** (`APP_ENV=dev` or `APP_ENV=test`).

### 4. Get Verification Token (Development Only)

**GET** `/api/dev/verification-token?email=john@example.com`

Retrieves verification token for testing. **Development only** - not available in production.

**Purpose:** Test email verification flow without receiving actual emails.

**Response:**
```json
{
  "success": true,
  "message": "Verification token retrieved (Dev Mode Only)",
  "code": "DEV_TOKEN_RETRIEVED",
  "warning": "⚠️ This endpoint is for DEVELOPMENT ONLY - Do not expose in production",
  "data": {
    "email": "john@example.com",
    "verificationToken": "abc123xyz...",
    "verificationUrl": "https://easysave.local/verify-email/abc123xyz...",
    "apiVerifyUrl": "/api/verify-email"
  }
}
```

**Usage Example:**
```bash
# Get token for testing
TOKEN=$(curl -s "http://localhost:8000/api/dev/verification-token?email=john@example.com" \
  | jq -r '.data.verificationToken')

# Use token to verify
curl -X POST http://localhost:8000/api/verify-email \
  -H "Content-Type: application/json" \
  -d "{\"token\": \"$TOKEN\"}"
```

---

### 5. Check Email Mailbox (Development Only)

**GET** `/api/dev/mailbox?email=john@example.com`

Checks user's email verification status and token. **Development only**.

**Purpose:** Debug email verification status during development.

**Response:**
```json
{
  "success": true,
  "message": "User email status (Dev Mode Only)",
  "code": "DEV_MAILBOX_INFO",
  "data": {
    "email": "john@example.com",
    "isVerified": false,
    "status": "PENDING",
    "verificationToken": "abc123xyz...",
    "verificationLink": "https://easysave.local/verify-email/abc123xyz...",
    "apiVerifyEndpoint": "POST /api/verify-email",
    "apiVerifyPayload": {
      "token": "abc123xyz..."
    }
  }
}
```

---

## Mobile App Implementation Guide

### iOS/Swift Example

```swift
import Foundation

class EmailVerificationService {
    let baseURL = "http://localhost:8000/api"
    var jwtToken: String?
    
    // STEP 1: Register user
    func register(username: String, email: String, password: String) async {
        let url = URL(string: "\(baseURL)/register")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body: [String: Any] = [
            "username": username,
            "email": email,
            "plainPassword": password,
            "agreeTerms": true
        ]
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        
        let (data, response) = try await URLSession.shared.data(for: request)
        if let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] {
            if let token = json["token"] as? String {
                self.jwtToken = token
                print("✅ Registration successful, JWT token received")
                print("📧 Check email for verification link")
            }
        }
    }
    
    // STEP 2: Verify email with token (user copies from email)
    func verifyEmail(token: String) async {
        let url = URL(string: "\(baseURL)/verify-email")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body: [String: String] = ["token": token]
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        
        let (data, response) = try await URLSession.shared.data(for: request)
        if let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] {
            if let success = json["success"] as? Bool, success {
                print("✅ Email verified successfully!")
            }
        }
    }
    
    // STEP 3: Check verification status
    func checkVerificationStatus() async {
        guard let token = jwtToken else { return }
        
        let url = URL(string: "\(baseURL)/verification-status")!
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        
        let (data, response) = try await URLSession.shared.data(for: request)
        if let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] {
            if let data = json["data"] as? [String: Any],
               let isVerified = data["isVerified"] as? Bool {
                print("Verified: \(isVerified)")
            }
        }
    }
}
```

### Android/Kotlin Example

```kotlin
import android.content.Context
import kotlinx.coroutines.*
import okhttp3.*
import org.json.JSONObject

class EmailVerificationService(private val context: Context) {
    private val baseURL = "http://localhost:8000/api"
    private val client = OkHttpClient()
    private var jwtToken: String? = null
    
    // STEP 1: Register user
    suspend fun register(
        username: String,
        email: String,
        password: String
    ) = withContext(Dispatchers.IO) {
        val body = JSONObject().apply {
            put("username", username)
            put("email", email)
            put("plainPassword", password)
            put("agreeTerms", true)
        }
        
        val request = Request.Builder()
            .url("$baseURL/register")
            .post(RequestBody.create("application/json".toMediaType(), body.toString()))
            .build()
        
        val response = client.newCall(request).execute()
        val json = JSONObject(response.body?.string() ?: "")
        
        if (json.getBoolean("success")) {
            jwtToken = json.getString("token")
            println("✅ Registration successful, JWT token received")
            println("📧 Check email for verification link")
        }
    }
    
    // STEP 2: Verify email with token
    suspend fun verifyEmail(token: String) = withContext(Dispatchers.IO) {
        val body = JSONObject().apply {
            put("token", token)
        }
        
        val request = Request.Builder()
            .url("$baseURL/verify-email")
            .post(RequestBody.create("application/json".toMediaType(), body.toString()))
            .build()
        
        val response = client.newCall(request).execute()
        val json = JSONObject(response.body?.string() ?: "")
        
        if (json.getBoolean("success")) {
            println("✅ Email verified successfully!")
        }
    }
}
```

---

## Error Handling

### Common Error Codes

| Code | Status | Meaning | Action |
|------|--------|---------|--------|
| `MISSING_TOKEN` | 400 | No token provided | Ensure token is extracted from email |
| `INVALID_TOKEN` | 400 | Token expired/not found | Ask user to request new verification email |
| `VERIFICATION_ERROR` | 500 | Server error | Retry or contact support |
| `UNAUTHORIZED` | 401 | JWT token missing | User must login first |
| `ALREADY_VERIFIED` | 400 | Email already verified | Skip to next step |
| `EXEMPT_FROM_VERIFICATION` | 400 | Admin/Staff account | No verification needed |
| `EMAIL_SEND_FAILED` | 500 | Email service error | Check server logs |

---

## Testing Checklist - Mobile App

- [ ] User registers via `/api/register`
- [ ] Verification email received
- [ ] Token extracted from email or via `/api/dev/verification-token` (dev only)
- [ ] Token sent to `/api/verify-email`
- [ ] Verification successful response received
- [ ] Check `/api/verification-status` returns `isVerified: true`
- [ ] User can login via `/api/login`
- [ ] Staff/Admin users skip email verification requirement
- [ ] Resend link works via `/api/resend-verification`
- [ ] Error messages display correctly on mobile UI

---

## Role-Based Verification

| User Type | Must Verify Email? | Can Access App? |
|-----------|--------------------|-----------------| 
| Regular User | ✅ **YES** | ❌ Until verified |
| Staff (ROLE_STAFF) | ❌ **NO** | ✅ Immediately |
| Admin (ROLE_ADMIN) | ❌ **NO** | ✅ Immediately |
| OAuth User | ❌ (Auto-verified) | ✅ Immediately |

---

## Environment Configuration

### Development (Testing)

```env
APP_ENV=dev
MAILER_DSN=smtp://localhost:1025  # MailHog for testing
```

Then use dev endpoints:
- `GET /api/dev/verification-token?email=...`
- `GET /api/dev/mailbox?email=...`

### Production

```env
APP_ENV=prod
MAILER_DSN=brevo+api://YOUR_API_KEY@default
```

Dev endpoints not available (403 Forbidden).

---

## Deep Linking from Email

Email contains link: `https://easysave.local/verify-email/abc123xyz...`

**Mobile App Implementation:**
```swift
// In AppDelegate or SceneDelegate
func scene(_ scene: UIScene, continue userActivity: NSUserActivity) {
    guard userActivity.activityType == NSUserActivityTypeBrowsingWeb,
          let url = userActivity.webpageURL else { return }
    
    if url.path.contains("/verify-email/") {
        if let token = url.lastPathComponent {
            // Extract token and call API
            verificationService.verifyEmail(token: token)
        }
    }
}
```

---

## Support

- 📚 Full API docs: [MOBILE_API_DOCUMENTATION.md](../docs/MOBILE_API_DOCUMENTATION.md)
- 🧪 Test guide: [MOBILE_API_TEST_GUIDE.md](../docs/MOBILE_API_TEST_GUIDE.md)
- 📋 Quick reference: [MOBILE_API_QUICK_REFERENCE.md](../docs/MOBILE_API_QUICK_REFERENCE.md)
- 🔌 Email setup: [EMAIL_SETUP_GUIDE.md](../EMAIL_SETUP_GUIDE.md)

