# EasySave Mobile API Documentation

**Version**: 1.0  
**Base URL**: `http://localhost:8000/api/mobile`  
**API Type**: RESTful JSON API  
**Authentication**: JWT Bearer Token (for protected endpoints)

---

## Table of Contents

1. [Authentication](#authentication)
2. [Request/Response Format](#requestresponse-format)
3. [Endpoints](#endpoints)
4. [Error Handling](#error-handling)
5. [Examples](#examples)
6. [Rate Limiting](#rate-limiting)
7. [Pagination](#pagination)
8. [Status Codes](#status-codes)

---

## Authentication

### JWT Bearer Token

Protected endpoints require authentication using a JWT Bearer token.

**How to get a token:**

1. Register a new account:
   ```
   POST /api/register
   ```

2. Login:
   ```
   POST /api/login
   ```

3. Use the returned `token` in subsequent requests:
   ```
   Authorization: Bearer <your_jwt_token>
   ```

### Example Header
```
GET /api/mobile/user/profile HTTP/1.1
Host: localhost:8000
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## Request/Response Format

### Standard Response Format

All endpoints return JSON with this structure:

```json
{
  "success": true|false,
  "data": {},
  "message": "Human-readable message",
  "error": "Error code (if failed)"
}
```

### HTTP Methods

- **GET**: Retrieve data (safe, idempotent)
- **POST**: Create data
- **PUT**: Update data
- **DELETE**: Remove data

### Content-Type

All requests should include:
```
Content-Type: application/json
```

---

## Endpoints

### 1. List Products

**GET** `/products`

Retrieve a paginated list of active products with filtering and sorting options.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number (starts at 1) |
| `limit` | integer | 20 | Items per page (max: 100) |
| `category` | integer | - | Filter by category ID |
| `search` | string | - | Search in name, brand, description |
| `sort` | string | latest | Sort option: `latest`, `price_asc`, `price_desc`, `name_asc` |

#### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Product Name",
        "brand": "Brand Name",
        "price": 29.99,
        "stock": 50,
        "image": "/path/to/image.jpg",
        "category": {
          "id": 1,
          "name": "Electronics"
        },
        "is_active": true
      }
    ],
    "pagination": {
      "current_page": 1,
      "page_size": 20,
      "total_items": 150,
      "total_pages": 8,
      "has_next": true,
      "has_previous": false
    }
  },
  "message": "Products retrieved successfully"
}
```

#### Example Requests

```bash
# Get first page of products
curl http://localhost:8000/api/mobile/products

# Get products with custom pagination
curl http://localhost:8000/api/mobile/products?page=2&limit=10

# Search products
curl http://localhost:8000/api/mobile/products?search=laptop

# Filter by category
curl http://localhost:8000/api/mobile/products?category=5

# Sort by price ascending
curl http://localhost:8000/api/mobile/products?sort=price_asc

# Complex query
curl "http://localhost:8000/api/mobile/products?page=1&limit=15&category=2&search=phone&sort=price_asc"
```

---

### 2. Get Product Details

**GET** `/products/{id}`

Retrieve detailed information about a specific product.

#### URL Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Product ID (required) |

#### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Smart Phone X1",
    "brand": "TechBrand",
    "description": "High-performance smartphone with advanced features",
    "price": 799.99,
    "stock": 25,
    "image": "/uploads/products/phone1.jpg",
    "category": {
      "id": 1,
      "name": "Electronics",
      "description": "Electronic devices and gadgets"
    },
    "created_at": "2024-03-15 10:30:00",
    "updated_at": "2024-03-20 15:45:00",
    "is_active": true,
    "created_by": {
      "id": 5,
      "username": "admin"
    }
  },
  "message": "Product retrieved successfully"
}
```

#### Response (404 Not Found)

```json
{
  "success": false,
  "error": "Product not found",
  "message": "The requested product does not exist or is not available"
}
```

#### Example Requests

```bash
# Get product details
curl http://localhost:8000/api/mobile/products/1

# Get a non-existent product
curl http://localhost:8000/api/mobile/products/9999
```

---

### 3. List Categories

**GET** `/categories`

Retrieve all product categories.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `include_count` | boolean | false | Include product count per category |

#### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Electronics",
        "description": "Electronic devices and gadgets",
        "product_count": 42
      },
      {
        "id": 2,
        "name": "Clothing",
        "description": "Apparel and fashion items",
        "product_count": 156
      },
      {
        "id": 3,
        "name": "Home & Garden",
        "description": "Household and garden products",
        "product_count": 89
      }
    ],
    "total": 3
  },
  "message": "Categories retrieved successfully"
}
```

#### Example Requests

```bash
# Get all categories
curl http://localhost:8000/api/mobile/categories

# Get categories with product counts
curl http://localhost:8000/api/mobile/categories?include_count=true
```

---

### 4. Get User Profile

**GET** `/user/profile`

Retrieve authenticated user's profile information.

**Authentication Required**: ✅ (Bearer Token)

#### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 123,
    "username": "john_doe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "is_verified": true,
    "is_active": true,
    "roles": ["ROLE_USER"],
    "created_at": "2024-01-15 10:30:00",
    "profile_picture": "/uploads/profiles/john_doe.jpg"
  },
  "message": "User profile retrieved successfully"
}
```

#### Response (401 Unauthorized)

```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "User authentication is required"
}
```

#### Example Request

```bash
# Get user profile (requires authentication)
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost:8000/api/mobile/user/profile
```

---

### 5. Get User Orders

**GET** `/user/orders`

Retrieve authenticated user's order history.

**Authentication Required**: ✅ (Bearer Token)

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `limit` | integer | 10 | Items per page (max: 50) |

#### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "order_number": "ORD-2024-001",
        "status": "completed",
        "total_amount": 249.99,
        "created_at": "2024-03-15 10:30:00",
        "updated_at": "2024-03-16 14:20:00"
      },
      {
        "id": 2,
        "order_number": "ORD-2024-002",
        "status": "pending",
        "total_amount": 99.99,
        "created_at": "2024-03-18 11:45:00",
        "updated_at": "2024-03-18 11:45:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "page_size": 10,
      "total_items": 2,
      "total_pages": 1
    }
  },
  "message": "User orders retrieved successfully"
}
```

#### Example Request

```bash
# Get user's orders
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost:8000/api/mobile/user/orders

# Get orders with custom pagination
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  "http://localhost:8000/api/mobile/user/orders?page=2&limit=5"
```

---

### 6. Get User Favorites

**GET** `/user/favorites`

Retrieve authenticated user's favorite products.

**Authentication Required**: ✅ (Bearer Token)

#### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "favorites": [
      {
        "id": 1,
        "product": {
          "id": 5,
          "name": "Wireless Headphones",
          "brand": "AudioBrand",
          "price": 149.99,
          "stock": 30,
          "image": "/uploads/products/headphones.jpg",
          "category": {
            "id": 1,
            "name": "Electronics"
          },
          "is_active": true
        },
        "added_at": "2024-03-10 15:30:00"
      }
    ],
    "total": 1
  },
  "message": "User favorites retrieved successfully"
}
```

#### Example Request

```bash
# Get user's favorite products
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost:8000/api/mobile/user/favorites
```

---

## Error Handling

### Error Response Format

All error responses follow this format:

```json
{
  "success": false,
  "error": "Error code",
  "message": "Human-readable error message"
}
```

### Common Errors

| Status | Error Code | Message |
|--------|-----------|---------|
| 400 | invalid_request | Invalid request parameters |
| 401 | unauthorized | Authentication is required |
| 404 | not_found | Resource not found |
| 422 | validation_error | Request validation failed |
| 429 | too_many_requests | Rate limit exceeded |
| 500 | server_error | Internal server error |

---

## Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request succeeded |
| 201 | Created | Resource created successfully |
| 204 | No Content | Request succeeded but no content returned |
| 400 | Bad Request | Invalid parameters or syntax |
| 401 | Unauthorized | Authentication required or invalid |
| 403 | Forbidden | Access forbidden |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation error |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | Service temporarily unavailable |

---

## Pagination

### Pagination Parameters

- **page**: Current page (starts at 1)
- **limit**: Items per page (defaults vary by endpoint)

### Pagination Response

```json
"pagination": {
  "current_page": 1,
  "page_size": 20,
  "total_items": 150,
  "total_pages": 8,
  "has_next": true,
  "has_previous": false
}
```

### Calculating Navigation

```
next_page = current_page + 1 (if has_next is true)
previous_page = current_page - 1 (if has_previous is true)
```

---

## Rate Limiting

**Limits**:
- **Unauthenticated**: 100 requests per hour per IP
- **Authenticated**: 1,000 requests per hour per user
- **Burst**: Max 10 requests per second

**Headers** (in response):
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1234567890
```

---

## Examples

### Complete Mobile App Flow

```javascript
// 1. Register
fetch('http://localhost:8000/api/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'john_doe',
    email: 'john@example.com',
    password: 'SecurePass123'
  })
});

// 2. Login
const loginResponse = await fetch('http://localhost:8000/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'john_doe',
    password: 'SecurePass123'
  })
});
const { token } = await loginResponse.json();

// 3. Get Products
const productsResponse = await fetch(
  'http://localhost:8000/api/mobile/products?page=1&limit=20',
  {
    headers: { 'Authorization': `Bearer ${token}` }
  }
);
const products = await productsResponse.json();

// 4. Get Product Details
const productResponse = await fetch(
  'http://localhost:8000/api/mobile/products/1',
  {
    headers: { 'Authorization': `Bearer ${token}` }
  }
);
const product = await productResponse.json();

// 5. Get User Profile
const profileResponse = await fetch(
  'http://localhost:8000/api/mobile/user/profile',
  {
    headers: { 'Authorization': `Bearer ${token}` }
  }
);
const profile = await profileResponse.json();

// 6. Get User Orders
const ordersResponse = await fetch(
  'http://localhost:8000/api/mobile/user/orders',
  {
    headers: { 'Authorization': `Bearer ${token}` }
  }
);
const orders = await ordersResponse.json();
```

### cURL Examples

```bash
# Get products
curl -s http://localhost:8000/api/mobile/products | jq

# Get product details
curl -s http://localhost:8000/api/mobile/products/1 | jq

# Get categories
curl -s http://localhost:8000/api/mobile/categories?include_count=true | jq

# Get user profile
curl -s \
  -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/mobile/user/profile | jq

# Get user orders
curl -s \
  -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/mobile/user/orders | jq
```

---

## Best Practices

### Mobile Development

1. **Cache Responses**: Cache products/categories locally
2. **Use Pagination**: Always use limits to reduce data transfer
3. **Handle Errors**: Always check `success` field and handle errors gracefully
4. **Pagination**: Implement infinite scroll or "Load More" for product lists
5. **Token Refresh**: Refresh JWT token before expiration
6. **Retry Logic**: Implement exponential backoff for failed requests
7. **Compression**: Support gzip compression for responses
8. **Timeouts**: Set reasonable request timeouts (10-30 seconds)

### API Gateway Considerations

- All endpoints are accessible from mobile clients
- CORS is configured for mobile domains
- Rate limiting prevents abuse
- JWT tokens should be stored securely in the app

---

## Support & Feedback

For issues or feature requests, contact the development team.

**Last Updated**: April 14, 2026  
**API Version**: 1.0
