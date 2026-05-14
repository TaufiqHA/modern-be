# Entity Relationship Diagram (ERD) - Modern Store

Dokumen ini menjelaskan struktur data dan hubungan antar entitas dalam aplikasi Modern Store.

## 1. Diagram ERD (Mermaid)

```mermaid
erDiagram
    USERS ||--o{ ADDRESSES : "has"
    USERS ||--o{ ORDERS : "places"
    USERS ||--o{ JASTIP_REQUESTS : "requests"
    
    CATEGORIES ||--o{ PRODUCTS : "contains"
    COLLECTIONS ||--o{ PRODUCTS : "includes"
    
    ORDERS ||--|{ ORDER_ITEMS : "consists of"
    ORDERS }o--|| ADDRESSES : "ships to"
    
    PRODUCTS ||--o{ ORDER_ITEMS : "ordered as"

    USERS {
        string id PK
        string name
        string email UK
        string password_hash
        string phone
        string avatar_url
        string role "user | admin"
        timestamp created_at
    }

    ADDRESSES {
        int id PK
        string user_id FK
        string label "Rumah | Kantor"
        string recipient_name
        string phone_number
        text full_address
        boolean is_default
    }

    CATEGORIES {
        int id PK
        string name
        string slug UK
        string icon
    }

    COLLECTIONS {
        int id PK
        string title
        string slug UK
        text description
        string image_url
    }

    PRODUCTS {
        int id PK
        int category_id FK
        int collection_id FK "nullable"
        string name
        string slug UK
        text description
        decimal price
        int stock
        float rating
        string image_url
        boolean is_featured
    }

    ORDERS {
        string id PK "ORD-XXXX"
        string user_id FK
        int address_id FK
        timestamp order_date
        decimal total_amount
        string type "ready_stock | pre_order | jastip"
        string status "pending | processed | shipped | completed"
        string payment_status "unpaid | paid | refunded"
        string tracking_number
    }

    ORDER_ITEMS {
        int id PK
        string order_id FK
        int product_id FK "nullable"
        string product_name "snapshot"
        int quantity
        decimal unit_price
        decimal subtotal
    }

    JASTIP_REQUESTS {
        string id PK "JS-XXXX"
        string user_id FK
        string product_name
        string product_link
        string image_url
        int quantity
        text notes
        string status "pending | quotation | approved | rejected"
        decimal quote "nullable"
        timestamp created_at
    }
```

---

## 2. Definisi Entitas

### 2.1 USERS
Menyimpan informasi pengguna aplikasi, baik pembeli maupun administrator.
- `role`: Membedakan akses antara pengguna biasa dan admin dashboard.

### 2.2 ADDRESSES
Daftar alamat pengiriman yang disimpan oleh pengguna.
- Satu pengguna dapat memiliki banyak alamat (Rumah, Kantor, dll).
- `is_default`: Menandai alamat utama untuk checkout.

### 2.3 CATEGORIES
Kategori produk (misal: Sepatu, Pakaian, Aksesori).
- Digunakan untuk navigasi dan filter produk di halaman Shop.

### 2.4 COLLECTIONS
Grup produk berdasarkan tema tertentu (misal: "Edisi 01: Esensial Modern").
- Memiliki halaman detail tersendiri untuk menampilkan kurasi produk.

### 2.5 PRODUCTS
Katalog produk yang tersedia di toko.
- `stock`: Melacak ketersediaan barang.
- `collection_id`: Opsional, produk bisa saja tidak masuk ke koleksi tertentu.

### 2.6 ORDERS
Informasi utama transaksi pembelian.
- `id`: Menggunakan format khusus (misal: ORD-9921).
- `type`: Membedakan pesanan barang stok, pre-order, atau hasil jastip.

### 2.7 ORDER_ITEMS
Detail barang yang dibeli dalam satu pesanan.
- Menyimpan `product_name` dan `unit_price` saat transaksi untuk riwayat (mencegah data berubah jika produk asli diupdate).

### 2.8 JASTIP_REQUESTS
Permintaan khusus pengguna untuk barang yang tidak ada di katalog (Personal Shopper).
- Admin akan memberikan `quote` (penawaran harga) berdasarkan request ini.
- Jika disetujui, dapat dikonversi menjadi `ORDERS`.

---

## 3. Hubungan (Relationships)

1.  **Users - Addresses (1:N)**: Satu user bisa punya banyak alamat simpanan.
2.  **Users - Orders (1:N)**: Satu user bisa melakukan banyak pesanan.
3.  **Users - JastipRequests (1:N)**: Satu user bisa mengajukan banyak permintaan titipan.
4.  **Categories - Products (1:N)**: Satu kategori berisi banyak produk.
5.  **Collections - Products (1:N)**: Satu koleksi membawahi banyak produk.
6.  **Orders - OrderItems (1:N)**: Satu pesanan terdiri dari satu atau lebih item barang.
7.  **Orders - Addresses (N:1)**: Pesanan dikirim ke satu alamat spesifik milik user.
8.  **Products - OrderItems (1:N)**: Satu produk bisa muncul di banyak detail pesanan.
