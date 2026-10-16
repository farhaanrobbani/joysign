# 📄 Product Requirement Document (PRD)
## Aplikasi Web Tanda Tangan Elektronik (TTE)

* **Stack Target**: Laravel 13, PHP 8.5, PostgreSQL / MySQL
* **Versi Dokumen**: 1.0.0
* **Status**: Draft Requirements

---

## 1. Ringkasan Produk
Aplikasi Web TTE adalah platform pengelolaan dan penandatanganan dokumen digital berbasis web. Platform ini memfasilitasi unggah dokumen PDF, penempatan tanda tangan visual (QR Code), serta penandatanganan kriptografis digital untuk menjamin *integritas*, *keaslian*, dan *non-repudiation* (penangkalan) dokumen digital.

---

## 2. Tujuan & Sasaran (Goals & Objectives)
* **Integritas Dokumen**: Menjamin dokumen yang telah ditandatangani tidak dapat diubah tanpa terdeteksi (Hash Integrity).
* **Kemudahan Akses**: Antarmuka visual berbasis canvas interaktif untuk menentukan posisi tanda tangan ($X, Y$) pada halaman PDF.
* **Verifikasi Publik**: Menyediakan portal verifikasi terbuka untuk validasi keaslian dokumen via Scan QR Code atau Token (12 karakter).
* **Keamanan Kunci**: Pengelolaan pasang kunci (*Keypair*) kriptografi pengguna yang aman dan terenkripsi.
* **Pencatatan Audit**: Menyediakan log jejak audit komprehensif untuk setiap aksi terhadap dokumen.

---

## 3. User Persona & Hak Akses
* **System Admin**: Mengelola pengguna, konfigurasi sistem, dan memantau log aktivitas platform.
* **Signer (Penandatangan)**: Mengelola sertifikat digital, mengunggah dokumen, menempatkan tanda tangan visual, dan melakukan *signing*.
* **Public / Verifier**: Pihak eksternal yang memverifikasi keaslian dokumen melalui portal publik.

---

## 4. Persyaratan Fungsional (Functional Requirements)

### FR-01: Manajemen Pengguna & Sertifikat Digital
* Pembuatan akun pengguna oleh admin atau operator melalui panel Manajemen Pengguna (tanpa registrasi publik), dengan verifikasi identitas (NIK / Email / KTP).
* Penerbitan pasang kunci OpenSSL (Private & Public Key RSA 2048/4096-bit).
* Enkripsi Private Key berbasis Passphrase / App Secret (*Encryption at Rest*).

### FR-02: Pengelolaan & Visualisasi Dokumen
* Unggah dokumen PDF (Maksimal 20MB).
* Rendering *preview* PDF halaman per halaman.
* Fitur *Drag & Drop* penempatan tanda tangan visual (QR Code) pada koordinat $X, Y$.

### FR-03: Penandatanganan Kriptografis
* Perhitungan Hash SHA-256 dokumen asli.
* Pembubuhan Tanda Tangan Visual (QR Code + Logo Perusahaan opsional) ke file PDF.
* Penandatanganan digital menggunakan Private Key penandatangan (Cryptographic Signature).

### FR-04: Verifikasi Kebenaran Dokumen
* Portal publik verifikasi via QR Code (`/verify/{code}`).
* Portal publik verifikasi via *file upload* untuk mencocokkan Hash SHA-256.
* Indikator status integritas: **VALID**, **ALTERED/TAMPERED**, atau **EXPIRED**.

### FR-05: Audit Trail & Reporting
* Pencatatan otomatis log audit (Timestamp, IP Address, User Agent, Hash SHA-256, Action Type).
* Ekspor sertifikat pembuktian penandatanganan (*Audit Certificate PDF*).

---

## 5. Persyaratan Non-Fungsional (Non-Functional Requirements)
* **Performa**: Waktu pemrosesan penandatanganan PDF $< 3$ detik untuk dokumen hingga 10 halaman.
* **Keamanan**: Private Key tidak pernah disimpan dalam bentuk *plaintext*. Memenuhi standar OWASP Top 10.
* **Kompatibilitas**: Berjalan optimal pada PHP 8.5 dan Laravel 13.
