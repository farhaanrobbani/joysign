# 🗄️ Database Schema & Migrations (`DATABASE.md`)

---

## 1. Entity Relationship Overview

* `users` **1 --- N** `digital_certificates`
* `users` **1 --- N** `documents`
* `documents` **1 --- N** `document_signatures`
* `documents` **1 --- N** `audit_logs`

---

## 2. Table Schemas

### 1. Table: `users`
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | `CHAR(26)` | PK, ULID | Identifier Unik |
| `name` | `VARCHAR(255)` | NOT NULL | Nama Lengkap |
| `email` | `VARCHAR(255)` | UNIQUE, NOT NULL | Alamat Email |
| `nik` | `VARCHAR(16)` | UNIQUE, NULLABLE | NIK Identitas |
| `jabatan` | `VARCHAR(255)` | NULLABLE | Jabatan Penandatangan |
| `unit_kerja` | `VARCHAR(255)` | NULLABLE | Unit Kerja Penandatangan |
| `instansi` | `VARCHAR(255)` | NULLABLE | Instansi Penandatangan |
| `password` | `VARCHAR(255)` | NOT NULL | Password Hash |
| `created_at` | `TIMESTAMP` | NULLABLE | |
| `updated_at` | `TIMESTAMP` | NULLABLE | |

### 2. Table: `digital_certificates`
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | `CHAR(26)` | PK, ULID | Identifier Unik |
| `user_id` | `CHAR(26)` | FK -> `users.id` | Pemilik Kunci |
| `public_key` | `TEXT` | NOT NULL | OpenSSL Public Key PEM |
| `encrypted_private_key` | `TEXT` | NOT NULL | Private Key Terenkripsi |
| `key_algorithm` | `VARCHAR(50)` | DEFAULT 'RSA-2048' | Algoritma Kunci |
| `is_active` | `BOOLEAN` | DEFAULT true | Status Aktif |
| `created_at` | `TIMESTAMP` | NULLABLE | |

### 3. Table: `documents`
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | `CHAR(26)` | PK, ULID | Identifier Unik |
| `user_id` | `CHAR(26)` | FK -> `users.id` | Pemilik/Pengunggah |
| `title` | `VARCHAR(255)` | NOT NULL | Judul Dokumen |
| `original_path` | `VARCHAR(500)` | NOT NULL | Path File Draft |
| `signed_path` | `VARCHAR(500)` | NULLABLE | Path File Hasil TTE |
| `original_hash` | `VARCHAR(64)` | NOT NULL | SHA-256 Dokumen Asli |
| `signed_hash` | `VARCHAR(64)` | NULLABLE | SHA-256 Dokumen TTE |
| `status` | `ENUM` | 'draft', 'signed', 'revoked' | Status Dokumen |
| `created_at` | `TIMESTAMP` | NULLABLE | |

### 4. Table: `document_signatures`
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | `CHAR(26)` | PK, ULID | Identifier Unik |
| `document_id` | `CHAR(26)` | FK -> `documents.id` | Dokumen Terkait |
| `user_id` | `CHAR(26)` | FK -> `users.id` | Penandatangan |
| `verification_code` | `VARCHAR(64)` | UNIQUE, NOT NULL | Kode Unik QR Code |
| `page_number` | `INT` | NOT NULL | Halaman TTE |
| `coord_x` | `FLOAT` | NOT NULL | Koordinat X (Point) |
| `coord_y` | `FLOAT` | NOT NULL | Koordinat Y (Point) |
| `width` | `FLOAT` | NOT NULL | Lebar Visual TTE |
| `height` | `FLOAT` | NOT NULL | Tinggi Visual TTE |
| `cryptographic_signature` | `TEXT` | NOT NULL | Digital Signature Output |
| `signed_at` | `TIMESTAMP` | NOT NULL | Waktu Penandatanganan |

### 5. Table: `audit_logs`
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | `CHAR(26)` | PK, ULID | Identifier Unik |
| `document_id` | `CHAR(26)` | NULLABLE, FK | Dokumen Terkait |
| `user_id` | `CHAR(26)` | NULLABLE, FK | User Pelaku |
| `event` | `VARCHAR(100)` | NOT NULL | E.g. `document.signed` |
| `ip_address` | `VARCHAR(45)` | NOT NULL | IP Pelaku |
| `user_agent` | `TEXT` | NULLABLE | Browser/Device Info |
| `payload` | `JSON` | NULLABLE | Meta Data Tambahan |
| `created_at` | `TIMESTAMP` | NOT NULL | |
