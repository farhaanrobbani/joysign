# 🎨 Technical & Visual Design Specification (`DESIGN.md`)

---

## 1. Interactive Signature Placement Design

Pengguna menempatkan tanda tangan visual pada dokumen PDF menggunakan antarmuka berbasis **Canvas / PDF.js**:

1. **PDF Rendering**: Canvas merender setiap halaman PDF dalam skala piksel tertentu.
2. **Interactive Bounding Box**: Pengguna menggeser (*drag*) dan mengubah ukuran (*resize*) kotak tanda tangan.
3. **Coordinate Normalization**:
   - Koordinat UI (Piksel Canvas) dikonversi ke koordinat standar PDF Point ($1 \text{ pt} = \frac{1}{72} \text{ inci}$).
   - Rumus Konversi:
     $$X_{pdf} = \frac{X_{canvas}}{\text{Zoom Ratio}} \times \left(\frac{72}{\text{DPI}}\right)$$
     $$Y_{pdf} = \frac{Y_{canvas}}{\text{Zoom Ratio}} \times \left(\frac{72}{\text{DPI}}\right)$$

---

## 2. Flow Diagram Penandatanganan (Signing Workflow)

[User] -> Select Document -> Drag & Drop TTD Canvas
│
▼
[Submit Request] -> (Doc ID, Page, X, Y, Width, Height, Passphrase)
│
▼
[Laravel Controller] -> Validate Form Request
│
├──> [CryptoService] -> Decrypt Private Key using Passphrase
│
├──> [QRCodeService] -> Generate Verification QR Code Image
│
├──> [PdfSignerService] -> Render PNG Visual + QR Code onto PDF at (X, Y)
│
├──> [CryptoService] -> Hash Signed PDF & Generate RSA Signature
│
└──> [AuditService] -> Save Document, Signature Record & Audit Trail
---
## 3. Visual Signature Layout Standard

Elemen visual tanda tangan yang ditempelkan pada PDF terdiri dari:
* **Left Section**: QR Code Verifikasi Unik (menuju portal `/verify/{code}`).
* **Right Section**: Logo Perusahaan (opsional, **per-perusahaan** — diunggah operator di halaman `/company`; bila perusahaan belum punya logo, area ini dikosongkan dan dokumen hanya memuat QR Code. Tanpa fallback ke logo global situs).
* **Footer Pernyataan**: di bawah setiap halaman PDF (bukan hanya halaman kotak tanda tangan), terdiri dari **garis tipis abu-abu sebagai separator** lalu dua baris rata kiri helvetica 10pt:
  1. *"Dokumen ini telah ditandatangani secara elektronik."* (reguler)
  2. *"Token: {XXXX-XXXX-XXXX}"* (**bold**)

  Di sebelah kiri teks ada **logo situs** (setting admin `site.logo_path`, tinggi ±14pt, skala proporsional) bila tersedia dan berformat PNG/JPG; tanpa logo (atau SVG), teks full width. **Header & footer default TCPDF dinonaktifkan** (subclass `Fpdi` dengan `Header()`/`Footer()` kosong) — tanpa garis judul di atas halaman maupun nomor halaman "n / n" bawaan TCPDF. Footer hanya **satu blok** berisi token **penanda tangan pertama** — semua QR menghasilkan halaman verifikasi identik (menampilkan seluruh penanda tangan). Footer ditulis **hanya saat embed tanda tangan pertama**; embed co-sign tidak menulis footer lagi (footer terbawa dari PDF sumber, teks tidak dobel). Token = `verification_code` 12 karakter Base32 (tanpa 0/O/1/I), ditampilkan berkelompok `XXXX-XXXX-XXXX`, sama dengan isi QR. Logo dibake saat penanda tangan (mengikuti kondisi setting saat itu).

> Keduanya digambar full-area pada posisi (X, Y, width, height) yang diberikan; logo diskalakan proporsional (contain) ke dalam bagiannya. Tidak ada teks metadata (Signed by/Date/Hash) di dalam kotak visual — pernyataan token hanya di footer halaman.

---

## 4. Portal Verifikasi Dokumen (UI)

Halaman hasil `/verify/{code}` menampilkan kartu status dengan urutan:

1. Badge status (VALID / TAMPERED / EXPIRED / REVOKED / NOT FOUND) + pesan.
2. **Token** (token penanda tangan pertama, format `XXXX-XXXX-XXXX`) — di paling atas detail. Lookup menerima input dengan/s tanpa dash dan huruf besar/kecil; kode lama (64 hex) tetap diterima.
3. Judul Dokumen.
4. **Blok per Penandatangan** (semua baris `document_signatures`, urut `signed_at` — bukan hanya kode yang di-query):
   - Nama (`signer_name` snapshot fallback `signer.name`), diberi nomor "Penandatangan 1/2" bila >1;
   - **Jabatan → Unit Kerja → Instansi** (baris disembunyikan bila kosong; live dari `users`);
   - Waktu Tanda Tangan;
   - Berlaku Sertifikat (per penanda tangan).
5. Tombol **Lihat Dokumen** → `GET /verify/{code}/document` — menyajikan PDF hasil TTE secara `inline` (tab baru) + audit `document.viewed`. Tidak tampil untuk UNSIGNED ORIGINAL / NOT FOUND.