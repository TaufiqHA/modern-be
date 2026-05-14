# API Reference - Modern Store

Dokumen ini berisi spesifikasi API yang diperlukan untuk mendukung fungsionalitas aplikasi Modern Store.

**Base URL:** `https://localhost:8000/api`

---

## 1. Authentication

### POST `/auth/register`
Mendaftarkan pengguna baru.
- **Request:**
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "securepassword123",
    "phone": "08123456789"
  }
  ```
- **Response (201 Created):**
  ```json
  {
    "status": "success",
    "token": "eyJhbG...",
    "user": {
      "id": "u123",
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user"
    }
  }
  ```

### POST `/auth/login`
Masuk ke akun.
- **Request:**
  ```json
  {
    "email": "john@example.com",
    "password": "securepassword123"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "token": "eyJhbG...",
    "user": {
      "id": "u123",
      "name": "John Doe",
      "role": "user"
    }
  }
  ```

---

## 2. Profile & Addresses

### GET `/user/me`
Mendapatkan data profil pengguna yang sedang login.
- **Header:** `Authorization: Bearer <token>`
- **Response (200 OK):**
  ```json
  {
    "id": "u123",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "08123456789",
    "avatar": "https://...",
    "role": "user"
  }
  ```

### GET `/user/addresses`
Mendapatkan daftar alamat pengiriman.
- **Response (200 OK):**
  ```json
  [
    {
      "id": 1,
      "label": "Rumah",
      "recipient": "John Doe",
      "phone": "08123456789",
      "detail": "Jl. Minimalist No. 42, Jakarta",
      "is_default": true
    }
  ]
  ```

### POST `/user/addresses`
Menambahkan alamat baru.
- **Request:**
  ```json
  {
    "label": "Kantor",
    "recipient": "John Doe",
    "phone": "08123456789",
    "detail": "Gedung Modern Lt. 5, Jakarta"
  }
  ```

---

## 3. Products & Catalog

### GET `/products`
Mendapatkan daftar produk dengan filter dan pagination.
- **Query Params:** `category`, `collection`, `search`, `page`, `limit`
- **Response (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "name": "White Sneakers",
        "price": 899000,
        "image": "https://...",
        "category": "Sepatu",
        "stock": 10
      }
    ],
    "meta": {
      "total": 48,
      "page": 1,
      "last_page": 5
    }
  }
  ```

### GET `/products/:id`
Detail produk spesifik.
- **Response (200 OK):**
  ```json
  {
    "id": 1,
    "name": "White Sneakers",
    "description": "Premium leather...",
    "price": 899000,
    "stock": 10,
    "images": ["https://...", "https://..."],
    "category": "Sepatu"
  }
  ```

### GET `/categories`
Daftar kategori produk.
- **Response (200 OK):**
  ```json
  [
    { "id": 1, "name": "Sepatu", "icon": "👟", "slug": "sepatu" }
  ]
  ```

---

## 4. Orders

### POST `/orders`
Membuat pesanan baru.
- **Request:**
  ```json
  {
    "address_id": 1,
    "items": [
      { "product_id": 1, "quantity": 2 }
    ],
    "payment_method": "midtrans_va"
  }
  ```
- **Response (201 Created):**
  ```json
  {
    "order_id": "ORD-9921",
    "total_amount": 1798000,
    "payment_url": "https://checkout.midtrans.com/..."
  }
  ```

### GET `/orders`
Riwayat pesanan pengguna.
- **Response (200 OK):**
  ```json
  [
    {
      "id": "ORD-9921",
      "status": "shipped",
      "total": 1798000,
      "date": "2024-05-12T10:00:00Z"
    }
  ]
  ```

---

## 5. Jastip (Personal Shopper)

### POST `/jastip/request`
Mengajukan permintaan titipan barang.
- **Request (Multipart/Form-Data):**
  - `product_name`: "Nike Dunk Low"
  - `product_link`: "https://nike.com/..."
  - `image`: [File Binary]
  - `quantity`: 1
  - `notes`: "Size 42"
- **Response (201 Created):**
  ```json
  {
    "request_id": "JS-551",
    "status": "pending",
    "message": "Request submitted successfully"
  }
  ```

### GET `/jastip/requests`
Daftar pengajuan jastip pengguna.
- **Response (200 OK):**
  ```json
  [
    {
      "id": "JS-551",
      "product": "Nike Dunk Low",
      "status": "quotation",
      "quote": 2500000
    }
  ]
  ```

---

## 6. Admin Endpoints

### GET `/admin/dashboard/stats`
Statistik untuk dashboard admin.
- **Response (200 OK):**
  ```json
  {
    "total_sales": 150000000,
    "active_orders": 24,
    "pending_jastip": 12,
    "low_stock": 5
  }
  ```

### PATCH `/admin/jastip/:id/quote`
Memberikan penawaran harga untuk request jastip.
- **Request:**
  ```json
  {
    "price": 2500000
  }
  ```

### PATCH `/admin/orders/:id/status`
Memperbarui status pesanan.
- **Request:**
  ```json
  {
    "status": "shipped",
    "tracking_number": "IDX-12345"
  }
  ```

### POST `/admin/products`
Menambah produk baru ke katalog.
- **Request:**
  ```json
  {
    "name": "New Product",
    "price": 500000,
    "category_id": 1,
    "stock": 100
  }
  ```
