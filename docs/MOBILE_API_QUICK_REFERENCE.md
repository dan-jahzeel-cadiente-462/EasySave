# Mobile API - Quick Reference Guide

**API Base URL**: `http://localhost:8000/api/mobile`

---

## 🚀 Quick Start

### 1. Get Products
```
GET /api/mobile/products?page=1&limit=20
```
**Public** - No auth required

### 2. Get Product Details
```
GET /api/mobile/products/{id}
```
**Public** - No auth required

### 3. Get Categories
```
GET /api/mobile/categories?include_count=true
```
**Public** - No auth required

### 4. Get User Profile
```
GET /api/mobile/user/profile
```
**🔒 Protected** - Requires JWT token

### 5. Get User Orders  
```
GET /api/mobile/user/orders?page=1&limit=10
```
**🔒 Protected** - Requires JWT token

### 6. Get User Favorites
```
GET /api/mobile/user/favorites
```
**🔒 Protected** - Requires JWT token

---

## 🔐 Authentication Flow

```
1. Register → POST /api/register
2. Login → POST /api/login (returns JWT token)
3. Use token in subsequent requests:
   Authorization: Bearer <your_token>
```

---

## 📊 Response Format

### Success (200 OK)
```json
{
  "success": true,
  "data": { /* actual data */ },
  "message": "Success message"
}
```

### Error (4xx/5xx)
```json
{
  "success": false,
  "error": "error_code",
  "message": "Error description"
}
```

---

## ⚡ Endpoint Summary

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/products` | ❌ | List products (paginated) |
| GET | `/products/{id}` | ❌ | Get product details |
| GET | `/categories` | ❌ | List all categories |
| GET | `/user/profile` | ✅ | Get logged-in user info |
| GET | `/user/orders` | ✅ | Get user's orders |
| GET | `/user/favorites` | ✅ | Get user's favorites |

---

## 🎯 Common Use Cases

### Search Products
```
GET /products?search=laptop&limit=20
```

### Filter by Category
```
GET /products?category=1&limit=20
```

### Sort by Price (Low to High)
```
GET /products?sort=price_asc&limit=20
```

### Get Second Page
```
GET /products?page=2&limit=20
```

---

## 🛠️ Integration Tips

### JavaScript/React
```javascript
const response = await fetch('/api/mobile/products');
const { data } = await response.json();
```

### Flutter/Dart
```dart
final response = await http.get(
  Uri.parse('http://localhost:8000/api/mobile/products'),
);
```

### Swift/iOS
```swift
let url = URL(string: "http://localhost:8000/api/mobile/products")!
let task = URLSession.shared.dataTask(with: url) { data, _, _ in
  let json = try! JSONDecoder().decode(/* ... */)
}
```

### Kotlin/Android
```kotlin
val request = Request.Builder()
  .url("http://localhost:8000/api/mobile/products")
  .build()
val response = okHttpClient.newCall(request).execute()
```

---

## 📱 Mobile App Architecture

```
┌─────────────────────┐
│   Mobile App        │
│  (iOS/Android)      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  API Layer                              │
│  - HTTP Client                          │
│  - Token Management                     │
│  - Error Handling                       │
│  - Caching                              │
└──────────────────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  EasySave API Endpoints                 │
│  /api/mobile/products                   │
│  /api/mobile/categories                 │
│  /api/mobile/user/*                     │
└─────────────────────────────────────────┘
```

---

## ✅ Test These Endpoints

### Without Authentication
```bash
# Get products
curl http://localhost:8000/api/mobile/products

# Get categories
curl http://localhost:8000/api/mobile/categories

# Get product details
curl http://localhost:8000/api/mobile/products/1
```

### With Authentication
```bash
# First, login to get token
TOKEN=$(curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"user","password":"pass"}' \
  | jq -r '.token')

# Use token in subsequent requests
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/mobile/user/profile
```

---

## 📊 Data Models

### Product
```json
{
  "id": 1,
  "name": "Product Name",
  "brand": "Brand",
  "price": 99.99,
  "stock": 50,
  "image": "/path/to/image.jpg",
  "category": { "id": 1, "name": "Category" },
  "is_active": true
}
```

### Category
```json
{
  "id": 1,
  "name": "Electronics",
  "description": "...",
  "product_count": 42
}
```

### User
```json
{
  "id": 1,
  "username": "user",
  "email": "user@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "is_verified": true,
  "roles": ["ROLE_USER"]
}
```

---

## 🔄 Pagination

All list endpoints support pagination:

```javascript
// Get page 2 with 50 items per page
fetch('/api/mobile/products?page=2&limit=50')
  .then(r => r.json())
  .then(({ data: { pagination } }) => {
    console.log(pagination.total_pages);
    console.log(pagination.has_next);
  });
```

---

## ⚠️ Error Codes

| Code | Message | Action |
|------|---------|--------|
| 400 | Invalid parameters | Check query parameters |
| 401 | Unauthorized | Include valid JWT token |
| 404 | Not found | Check ID is correct |
| 429 | Rate limit exceeded | Wait before retrying |
| 500 | Server error | Retry later |

---

## 📞 Support

See `MOBILE_API_DOCUMENTATION.md` for detailed documentation.

**Last Updated**: April 14, 2026
