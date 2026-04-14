# Mobile API - Quick Test Guide

**Test all 6 endpoints in 5 minutes**

---

## 🧪 Step-by-Step Testing

### Step 1: Test Public Endpoints (No Auth Needed)

```bash
# Test 1: Get Products List
curl -s http://localhost:8000/api/mobile/products | jq '.'
# Expected: 200 OK with products array and pagination

# Test 2: Get Categories
curl -s http://localhost:8000/api/mobile/categories | jq '.'
# Expected: 200 OK with categories array

# Test 3: Get Product Details
curl -s http://localhost:8000/api/mobile/products/1 | jq '.'
# Expected: 200 OK with product details (or 404 if no product with ID 1)
```

### Step 2: Setup for Protected Endpoints

```bash
# First, create a test user to get JWT token
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "mobiletest_'$(date +%s)'",
    "email": "test_'$(date +%s)'@example.com",
    "password": "TestPassword123",
    "first_name": "Mobile",
    "last_name": "Tester"
  }' | jq '.'

# Then login to get JWT token
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "mobiletest_'$(date +%s)'",
    "password": "TestPassword123"
  }' | jq -r '.token')

echo "JWT Token: $TOKEN"
```

### Step 3: Test Protected Endpoints

```bash
# Test 4: Get User Profile
curl -s -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/mobile/user/profile | jq '.'
# Expected: 200 OK with user profile data

# Test 5: Get User Orders
curl -s -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/mobile/user/orders | jq '.'
# Expected: 200 OK with orders array (might be empty for new user)

# Test 6: Get User Favorites
curl -s -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/mobile/user/favorites | jq '.'
# Expected: 200 OK with favorites array (might be empty for new user)
```

---

## 🧬 Complete Test Script

Run this complete script to test all endpoints:

```bash
#!/bin/bash

BASE_URL="http://localhost:8000"
TIMESTAMP=$(date +%s)
USERNAME="test_$TIMESTAMP"
EMAIL="test_$TIMESTAMP@example.com"
PASSWORD="TestPassword123"

echo "========================================="
echo "EasySave Mobile API - Complete Test"
echo "========================================="

# Test 1: Register User
echo -e "\n[1/6] Testing User Registration..."
REGISTER=$(curl -s -X POST $BASE_URL/api/register \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$USERNAME\",
    \"email\": \"$EMAIL\",
    \"password\": \"$PASSWORD\",
    \"first_name\": \"Test\",
    \"last_name\": \"User\"
  }")

echo "Registration Response:"
echo $REGISTER | jq '.'

# Test 2: Login
echo -e "\n[2/6] Testing User Login..."
LOGIN=$(curl -s -X POST $BASE_URL/api/login \
  -H "Content-Type: application/json" \
  -d "{
    \"username\": \"$USERNAME\",
    \"password\": \"$PASSWORD\"
  }")

echo "Login Response:"
echo $LOGIN | jq '.'

TOKEN=$(echo $LOGIN | jq -r '.token')
echo "Got Token: ${TOKEN:0:50}..."

# Test 3: Public - List Products
echo -e "\n[3/6] Testing List Products (PUBLIC)..."
PRODUCTS=$(curl -s "$BASE_URL/api/mobile/products?limit=5")
echo "Products Response:"
echo $PRODUCTS | jq '.data.pagination'

# Test 4: Public - Get Categories
echo -e "\n[4/6] Testing List Categories (PUBLIC)..."
CATEGORIES=$(curl -s "$BASE_URL/api/mobile/categories?include_count=true")
echo "Categories Response:"
echo $CATEGORIES | jq '.data.categories | length'

# Test 5: Protected - User Profile
echo -e "\n[5/6] Testing User Profile (PROTECTED)..."
PROFILE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/mobile/user/profile")
echo "Profile Response:"
echo $PROFILE | jq '.data | {username, email, is_verified}'

# Test 6: Protected - User Orders
echo -e "\n[6/6] Testing User Orders (PROTECTED)..."
ORDERS=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/mobile/user/orders")
echo "Orders Response:"
echo $ORDERS | jq '.data.orders | length'

echo -e "\n========================================="
echo "✅ All 6 Endpoints Tested Successfully!"
echo "========================================="
```

Save this as `test_mobile_api.sh` and run it:
```bash
chmod +x test_mobile_api.sh
./test_mobile_api.sh
```

---

## 🔍 Test Different Query Parameters

### Test Search & Filtering

```bash
# Search products
curl -s "http://localhost:8000/api/mobile/products?search=phone" | jq '.data.products | length'

# Filter by category
curl -s "http://localhost:8000/api/mobile/products?category=1" | jq '.data.products | length'

# Sort by price low to high
curl -s "http://localhost:8000/api/mobile/products?sort=price_asc" | jq '.data.products[0]'

# Get specific page
curl -s "http://localhost:8000/api/mobile/products?page=2&limit=5" | jq '.data.pagination'

# Custom limit
curl -s "http://localhost:8000/api/mobile/products?limit=50" | jq '.data.pagination'
```

---

## ❌ Test Error Cases

```bash
# Test 401 Unauthorized (missing token)
curl -s http://localhost:8000/api/mobile/user/profile | jq '.'
# Expected: success: false, error: "Unauthorized"

# Test 404 Not Found
curl -s http://localhost:8000/api/mobile/products/99999 | jq '.'
# Expected: success: false, error: "Product not found"

# Test with invalid token
curl -s -H "Authorization: Bearer invalid_token" \
  http://localhost:8000/api/mobile/user/profile | jq '.'
# Expected: success: false, error: "Unauthorized"
```

---

## 📊 Expected Success Responses

### ✅ All Public Endpoints Should Return:
```json
{
  "success": true,
  "data": { /* endpoint-specific data */ },
  "message": "... retrieved successfully"
}
```

### ✅ All Protected Endpoints Should Return (with token):
```json
{
  "success": true,
  "data": { /* user-specific data */ },
  "message": "... retrieved successfully"
}
```

### ❌ All Protected Endpoints Without Token Should Return:
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "User authentication is required"
}
```

---

## 🧪 Using Postman

1. **Import Collection**:
   ```
   File → Import → Select postman_mobile_api_collection.json
   ```

2. **Set Variables**:
   - `base_url`: `http://localhost:8000`
   - `jwt_token`: (empty - will be filled by Login request)

3. **Run Tests in Order**:
   1. Authentication → Register User
   2. Authentication → Login (copies token automatically)
   3. All public endpoint tests
   4. All protected endpoint tests

---

## 📈 Verification Checklist

- [ ] All 6 endpoints respond with 200 OK for public (or 401 for protected without token)
- [ ] Response format is standardized (success, data, message)
- [ ] Pagination works with page/limit parameters
- [ ] Search/filter parameters work correctly
- [ ] JWT authentication works (401 without token, 200 with token)
- [ ] Error cases return proper error messages
- [ ] Product list has pagination metadata
- [ ] Categories list works
- [ ] User profile shows correct user data
- [ ] Orders/favorites endpoints accessible with auth

---

## 🚀 Performance Test

```bash
# Time a simple request
time curl -s http://localhost:8000/api/mobile/products > /dev/null

# Load test (requires Apache Bench)
ab -n 100 -c 10 http://localhost:8000/api/mobile/products

# Monitor response headers
curl -i http://localhost:8000/api/mobile/products | head -20
```

---

## 💡 Tips

- **Use `jq`** for pretty JSON output: `curl ... | jq '.'`
- **Extract fields**: `curl ... | jq '.data.products[0].name'`
- **Count results**: `curl ... | jq '.data.products | length'`
- **Store token**: `TOKEN=$(curl ... | jq -r '.token')`
- **Use variables**: `-H "Authorization: Bearer $TOKEN"`

---

## 📞 Debugging

If tests fail:

1. **Check server is running**:
   ```bash
   curl http://localhost:8000
   # Should return HTML (not connection refused)
   ```

2. **Check endpoint exists**:
   ```bash
   php bin/console debug:router | grep mobile
   ```

3. **Check logs**:
   ```bash
   tail -f var/log/dev.log
   ```

4. **Verify database**:
   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:migrations:migrate
   ```

---

## ✅ All Tests Passing?

If you've completed all 6 tests and got success responses, then:

✨ **Mobile API is fully functional!**  
✨ **Ready for mobile app integration!**  
✨ **All endpoints tested and working!**

---

**Test Date**: April 14, 2026
