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

## 7. Pengembangan Mendatang & API Tambahan (Berdasarkan UI Spec & PRD)

### 1. Manajemen User & Profil
Walaupun fitur ini ada di halaman "Akun User" pada UI Spec, API-nya masih sangat minim di dokumen referensi:
*   **Update Profile (PATCH /user/me)**: Dibutuhkan untuk menyimpan perubahan nama, email, nomor HP, dan foto profil yang ada di form edit profil.
*   **Update & Delete Alamat (PATCH/DELETE /user/addresses/:id)**: Di UI Spec terdapat tombol [Edit] dan [Hapus] pada kartu alamat, namun API References baru menyediakan GET dan POST saja.
*   **Ganti Password**: UI Spec menyebutkan adanya section terpisah untuk ganti password di sub-halaman profil.

### 2. Manajemen Produk (Admin & Umum)
Fitur CRUD produk belum terdokumentasi secara lengkap untuk sisi admin:
*   **Update Produk (PATCH /admin/products/:id)**: UI Admin menyediakan tombol [Edit] untuk produk, namun di API baru ada endpoint untuk POST (tambah baru).
*   **Hapus/Nonaktifkan Produk (DELETE atau PATCH /admin/products/:id/status)**: Dibutuhkan untuk tombol [Nonaktif] dan [Hapus] pada tabel produk admin.
*   **Riwayat Stok (GET /admin/products/:id/stock-logs)**: PRD mewajibkan adanya "Riwayat perubahan stok", dan UI Admin memiliki tabel log khusus untuk ini, tetapi API-nya belum ada.
*   **Daftar Koleksi (GET /collections)**: Meskipun collection muncul sebagai filter di API produk, endpoint untuk mengambil daftar semua koleksi (seperti "Edisi Esensial Modern") belum tersedia.

### 3. Alur Transaksi & Pembayaran
Beberapa endpoint kritikal untuk proses checkout dan konfirmasi masih hilang:
*   **Cek Ongkir Otomatis (POST /shipping/calculate)**: PRD menyebutkan integrasi API ongkir otomatis saat checkout, namun belum ada endpoint spesifik di dokumentasi API.
*   **Upload Bukti Transfer (POST /orders/:id/payment-proof)**: Halaman konfirmasi order dan PRD mewajibkan fitur unggah bukti bayar manual, namun endpoint ini belum tercatat.
*   **Konfirmasi Pembayaran oleh Admin**: Di UI Admin terdapat tombol [Konfirmasi Pembayaran] pada modal detail order yang berbeda dengan sekadar update status pengiriman.

### 4. Alur Jastip & Pre-Order
*   **Konversi Jastip ke PO**: PRD dan UI Admin menjelaskan alur di mana admin dapat mengubah request Jastip yang disetujui menjadi listing Pre-Order secara otomatis. API untuk proses konversi ini belum ada.
*   **Update Request Pre-Order**: PRD menyebutkan adanya "Halaman Request Preorder" di admin untuk memproses formulir yang masuk saat stok habis, namun API untuk mengelola data request PO ini belum tersedia.

### 5. Fitur Pendukung Lainnya
*   **Sistem Notifikasi (GET /notifications)**: UI Spec merancang ikon lonceng (bell) di navbar dan halaman notifikasi lengkap untuk admin dan user, namun API untuk mengambil daftar notifikasi ini belum tercantum.
*   **Search & Filter Lanjutan**: Di UI Katalog terdapat filter dual-handle range slider untuk harga, sehingga API GET /products mungkin perlu tambahan parameter min_price dan max_price agar lebih akurat.
