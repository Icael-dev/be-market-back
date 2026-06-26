# BE Market API Documentation

## Overview
This document describes all REST API endpoints for the BE Market e-commerce backend. All endpoints return JSON responses.

**Base URL:** `http://localhost/api`

**Authentication:** JWT tokens via `Authorization: Bearer {token}` header

---

## Authentication

### POST `/api/auth/register`
Register a new user account.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "full_name": "John Doe"
}
```

**Response (201):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "profile": {
    "id": "uuid",
    "user_id": "uuid",
    "email": "user@example.com",
    "full_name": "John Doe",
    "phone": null,
    "avatar_url": null,
    "is_admin": false,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

### POST `/api/auth/login`
Authenticate a user and receive a JWT token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):** Same as register response

### POST `/api/auth/logout` (Protected)
Logout the current user (revokes token).

**Response (200):**
```json
{
  "message": "Logged out successfully."
}
```

### GET `/api/auth/user` (Protected)
Get current authenticated user information.

**Response (200):** Same as register response

### POST `/api/auth/refresh` (Protected)
Refresh the JWT token.

**Response (200):** Same as register response

---

## Categories

### GET `/api/categories`
Get all active categories.

**Response (200):**
```json
[
  {
    "id": "uuid",
    "name": "Electronics",
    "slug": "electronics",
    "description": "Electronic devices",
    "image_url": "https://example.com/image.jpg",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

## Products

### GET `/api/products`
Get products with optional filters.

**Query Parameters:**
- `category` (string): Filter by category slug or ID
- `category_id` (string): Filter by category ID
- `search` (string): Search in name, description, SKU
- `brand` (string): Filter by brand (from specs)
- `min_price` (number): Minimum price
- `max_price` (number): Maximum price
- `featured` (boolean): Show only featured products
- `exclude_id` (string): Exclude specific product ID
- `sort` (string): Sort by `price_asc`, `price_desc`, `newest`, `name_asc`, `name_desc`
- `limit` (number): Limit results
- `page` (number): Page number (with pagination)
- `per_page` (number): Items per page (default: 20)

**Response (200):**
```json
[
  {
    "id": "uuid",
    "name": "Product Name",
    "slug": "product-name",
    "sku": "SKU123",
    "description": "Product description",
    "price": 99.99,
    "compare_price": 129.99,
    "stock": 50,
    "category_id": "uuid",
    "images": ["https://example.com/image1.jpg"],
    "specs": {"brand": "Brand", "color": "Black"},
    "is_featured": true,
    "is_active": true,
    "weight": 1.5,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "category": { ... }
  }
]
```

### GET `/api/products/{slug}`
Get a single product by slug.

**Response (200):** Same as product object above

---

## Cart

### GET `/api/cart` (Protected)
Get current user's cart items.

**Response (200):**
```json
[
  {
    "id": "uuid",
    "cart_id": "uuid",
    "product_id": "uuid",
    "quantity": 2,
    "product": { ... }
  }
]
```

### POST `/api/cart/items` (Protected)
Add item to cart.

**Request Body:**
```json
{
  "product_id": "uuid",
  "quantity": 1
}
```

**Response (201):** Cart item object

### PUT `/api/cart/items/{id}` (Protected)
Update cart item quantity.

**Request Body:**
```json
{
  "quantity": 3
}
```

**Response (200):** Updated cart item object

### DELETE `/api/cart/items/{id}` (Protected)
Remove item from cart.

**Response (204):** No content

### DELETE `/api/cart` (Protected)
Clear all items from cart.

**Response (204):** No content

---

## Orders

### POST `/api/checkout` (Protected)
Create an order from cart.

**Request Body:**
```json
{
  "shipping_address": {
    "full_name": "John Doe",
    "phone": "+1234567890",
    "address_line1": "123 Main St",
    "address_line2": "Apt 4B",
    "city": "New York",
    "region": "NY",
    "country": "USA"
  },
  "billing_address": { ... },
  "payment_method": "tmoney",
  "notes": "Please leave at door",
  "coupon_code": "SAVE10"
}
```

**Response (201):**
```json
{
  "id": "uuid",
  "order_number": "ORD-ABC123",
  "user_id": "uuid",
  "status": "processing",
  "payment_status": "pending",
  "payment_method": "tmoney",
  "subtotal": 99.99,
  "discount": 10.00,
  "shipping_fee": 0,
  "tax": 8.99,
  "total": 98.98,
  "shipping_address": { ... },
  "billing_address": { ... },
  "notes": "Please leave at door",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z",
  "items": [ ... ]
}
```

### GET `/api/orders` (Protected)
Get current user's orders.

**Query Parameters:**
- `limit` (number): Limit recent orders

**Response (200):** Array of order objects

### GET `/api/orders/{id}` (Protected)
Get specific order details.

**Response (200):** Order object with items

---

## Profile & Addresses

### GET `/api/profile` (Protected)
Get current user profile.

**Response (200):** User object with profile

### PUT `/api/profile` (Protected)
Update user profile.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "full_name": "John Doe",
  "phone": "+1234567890",
  "avatar_url": "https://example.com/avatar.jpg"
}
```

**Response (200):** Updated user object

### GET `/api/addresses` (Protected)
Get user's addresses.

**Response (200):**
```json
[
  {
    "id": "uuid",
    "user_id": "uuid",
    "label": "Home",
    "full_name": "John Doe",
    "phone": "+1234567890",
    "address_line1": "123 Main St",
    "address_line2": "Apt 4B",
    "city": "New York",
    "region": "NY",
    "country": "USA",
    "postal_code": "10001",
    "is_default": true,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### POST `/api/addresses` (Protected)
Create new address.

**Request Body:** Same as address object (without id, created_at)

**Response (201):** Created address object

### PUT `/api/addresses/{id}` (Protected)
Update address.

**Request Body:** Partial address object

**Response (200):** Updated address object

### DELETE `/api/addresses/{id}` (Protected)
Delete address.

**Response (200):**
```json
{
  "message": "Address deleted."
}
```

---

## Wishlist

### GET `/api/wishlist` (Protected)
Get user's wishlist.

**Response (200):**
```json
[
  {
    "id": "uuid",
    "user_id": "uuid",
    "product_id": "uuid",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "product": { ... }
  }
]
```

### POST `/api/wishlist` (Protected)
Add product to wishlist.

**Request Body:**
```json
{
  "product_id": "uuid"
}
```

**Response (201):** Wishlist item object

### DELETE `/api/wishlist/{id}` (Protected)
Remove from wishlist.

**Response (200):**
```json
{
  "message": "Wishlist item removed."
}
```

---

## Reviews

### GET `/api/reviews`
Get reviews (optionally filtered by product).

**Query Parameters:**
- `product_id` (string): Filter by product ID

**Response (200):**
```json
[
  {
    "id": "uuid",
    "product_id": "uuid",
    "user_id": "uuid",
    "rating": 5,
    "title": "Great product",
    "comment": "Really liked it",
    "is_verified_purchase": false,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "profile": { ... }
  }
]
```

### POST `/api/reviews` (Protected)
Submit a product review.

**Request Body:**
```json
{
  "product_id": "uuid",
  "rating": 5,
  "title": "Great product",
  "comment": "Really liked it"
}
```

**Response (201):** Created review object

---

## Loyalty Program

### GET `/api/loyalty/points` (Protected)
Get user's loyalty points balance.

**Response (200):**
```json
{
  "id": "uuid",
  "user_id": "uuid",
  "points": 100,
  "total_earned": 500,
  "total_redeemed": 400,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

### GET `/api/loyalty/transactions` (Protected)
Get user's loyalty transaction history.

**Response (200):**
```json
[
  {
    "id": "uuid",
    "user_id": "uuid",
    "type": "earned",
    "points": 50,
    "description": "Order #ORD-123",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

## Support

### POST `/api/support/tickets` (Protected)
Create a support ticket.

**Request Body:**
```json
{
  "subject": "Issue with order",
  "message": "I need help with my order",
  "priority": "normal"
}
```

**Response (201):**
```json
{
  "id": "uuid",
  "user_id": "uuid",
  "subject": "Issue with order",
  "status": "open",
  "priority": "normal",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z",
  "messages": [ ... ]
}
```

### GET `/api/support/tickets` (Protected)
Get user's support tickets.

**Response (200):** Array of ticket objects with messages

### GET `/api/support/tickets/{id}` (Protected)
Get specific ticket details.

**Response (200):** Ticket object with messages

### POST `/api/support/tickets/{id}/messages` (Protected)
Add message to ticket.

**Request Body:**
```json
{
  "message": "I need help with my order"
}
```

**Response (201):**
```json
{
  "id": "uuid",
  "ticket_id": "uuid",
  "user_id": "uuid",
  "message": "I need help with my order",
  "is_from_admin": false,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "profile": { ... }
}
```

---

## Public Content

### GET `/api/banners`
Get active banners.

**Response (200):**
```json
[
  {
    "id": "uuid",
    "title": "Summer Sale",
    "subtitle": "50% off",
    "image_url": "https://example.com/banner.jpg",
    "link_url": "/products",
    "button_text": "Shop Now",
    "position": "home",
    "is_active": true,
    "sort_order": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### GET `/api/blog-posts`
Get published blog posts.

**Query Parameters:**
- `published` (boolean): Filter by published status

**Response (200):**
```json
[
  {
    "id": "uuid",
    "title": "Blog Post Title",
    "slug": "blog-post-title",
    "content": "Full content...",
    "excerpt": "Short excerpt",
    "image_url": "https://example.com/blog.jpg",
    "author_id": "uuid",
    "is_published": true,
    "published_at": "2024-01-01T00:00:00.000000Z",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### GET `/api/blog-posts/{slug}`
Get single blog post by slug.

**Response (200):** Blog post object

### GET `/api/faqs`
Get active FAQs.

**Response (200):**
```json
[
  {
    "id": "uuid",
    "question": "How do I track my order?",
    "answer": "You can track your order from the orders page...",
    "category": "Orders",
    "sort_order": 1,
    "is_active": true,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### GET `/api/legal`
Get all legal pages.

**Response (200):**
```json
[
  {
    "id": "uuid",
    "slug": "terms-of-service",
    "title": "Terms of Service",
    "content": "Full terms content...",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### GET `/api/legal/{slug}`
Get specific legal page by slug.

**Response (200):** Legal page object

### GET `/api/flash-sales`
Get flash sales.

**Query Parameters:**
- `active` (boolean): Filter by currently active sales

**Response (200):**
```json
[
  {
    "id": "uuid",
    "product_id": "uuid",
    "discount_percentage": 30,
    "starts_at": "2024-01-01T00:00:00.000000Z",
    "ends_at": "2024-01-07T23:59:59.000000Z",
    "is_active": true,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "product": { ... }
  }
]
```

### GET `/api/settings`
Get store settings.

**Response (200):**
```json
{
  "shipping_fee": 5.00,
  "free_shipping_threshold": 50.00,
  "tax_rate": 0.1,
  "currency": "USD",
  "store_name": "BE Market",
  "store_email": "contact@bemarket.com",
  "store_phone": "+1234567890"
}
```

### POST `/api/newsletter`
Subscribe to newsletter.

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "message": "Subscribed successfully."
}
```

### POST `/api/stock-notifications`
Request stock notification for out-of-stock product.

**Request Body:**
```json
{
  "product_id": "uuid",
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "message": "Stock notification registered."
}
```

---

## Admin Endpoints

All admin endpoints require `auth:api` and `admin` middleware.

### Products
- `GET /api/admin/products` - List all products
- `POST /api/admin/products` - Create product
- `GET /api/admin/products/{id}` - Get product
- `PUT /api/admin/products/{id}` - Update product
- `DELETE /api/admin/products/{id}` - Delete product

### Categories
- `GET /api/admin/categories` - List all categories
- `POST /api/admin/categories` - Create category
- `GET /api/admin/categories/{id}` - Get category
- `PUT /api/admin/categories/{id}` - Update category
- `DELETE /api/admin/categories/{id}` - Delete category

### Banners
- `GET /api/admin/banners` - List all banners
- `POST /api/admin/banners` - Create banner
- `GET /api/admin/banners/{id}` - Get banner
- `PUT /api/admin/banners/{id}` - Update banner
- `DELETE /api/admin/banners/{id}` - Delete banner

### Blog Posts
- `GET /api/admin/blog-posts` - List all blog posts
- `POST /api/admin/blog-posts` - Create blog post
- `GET /api/admin/blog-posts/{id}` - Get blog post
- `PUT /api/admin/blog-posts/{id}` - Update blog post
- `DELETE /api/admin/blog-posts/{id}` - Delete blog post

### Coupons
- `GET /api/admin/coupons` - List all coupons
- `POST /api/admin/coupons` - Create coupon
- `GET /api/admin/coupons/{id}` - Get coupon
- `PUT /api/admin/coupons/{id}` - Update coupon
- `DELETE /api/admin/coupons/{id}` - Delete coupon

### Flash Sales
- `GET /api/admin/flash-sales` - List all flash sales
- `POST /api/admin/flash-sales` - Create flash sale
- `GET /api/admin/flash-sales/{id}` - Get flash sale
- `PUT /api/admin/flash-sales/{id}` - Update flash sale
- `DELETE /api/admin/flash-sales/{id}` - Delete flash sale

### Settings
- `GET /api/admin/settings` - Get all settings
- `PUT /api/admin/settings` - Update settings

### Orders
- `GET /api/admin/orders` - List all orders

### Customers
- `GET /api/admin/customers` - List all customers

### Statistics
- `GET /api/admin/statistics` - Get store statistics
- `GET /api/admin/export` - Export data

---

## Error Responses

### 400 Bad Request
```json
{
  "message": "Error message describing the issue"
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "Resource not found."
}
```

### 422 Validation Error
```json
{
  "errors": {
    "field_name": ["Error message"],
    "another_field": ["Another error message"]
  }
}
```

### 500 Server Error
```json
{
  "message": "Internal server error"
}
```

---

## Payment Methods

Supported payment methods:
- `tmoney` - T-Money mobile payment
- `flooz` - Flooz mobile payment
- `cash_on_delivery` - Cash on delivery

## Order Statuses

- `pending` - Order created, awaiting processing
- `confirmed` - Order confirmed by admin
- `processing` - Order being prepared
- `shipped` - Order shipped
- `delivered` - Order delivered
- `cancelled` - Order cancelled

## Payment Statuses

- `pending` - Payment pending
- `processing` - Payment being processed
- `paid` - Payment completed
- `failed` - Payment failed
- `refunded` - Payment refunded
