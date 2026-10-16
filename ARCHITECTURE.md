 `ARCHITECTURE.md` (System Architecture)

```markdown
# 🏛️ System Architecture (`ARCHITECTURE.md`)

---

## 1. High-Level Architecture Overview

Aplikasi ini menggunakan pola **Layered Modular Architecture** pada Laravel 13 untuk memisahkan domain logika kriptografi, pemrosesan media PDF, dan antarmuka pengguna.

┌─────────────────────────────────────────┐
│          Frontend Layer                 │
│   (Livewire 3 / Inertia + PDF.js)       │
└────────────────────┬────────────────────┘
│ HTTP / WebSockets
▼
┌─────────────────────────────────────────┐
│      HTTP & Controller Layer            │
│  (Form Requests, Middleware, Actions)   │
└────────────────────┬────────────────────┘
│
┌──────────────────────────┼──────────────────────────┐
▼                          ▼                          ▼
┌───────────────────────┐  ┌───────────────────────┐  ┌───────────────────────┐
│   Document Service    │  │    Crypto Service     │  │   PDF Engine Service  │
│ (State & Workflow)    │  │ (OpenSSL / Hashing)   │  │   (FPDI / TCPDF)      │
└───────────┬───────────┘  └───────────┬───────────┘  └───────────┬───────────┘
│                          │                          │
└──────────────────────────┼──────────────────────────┘
▼
┌─────────────────────────────────────────┐
│       Infrastructure & Storage          │
│  (DB PostgreSQL/MySQL, Private Storage) │
└─────────────────────────────────────────┘

---

## 2. Core Service Contracts

### `CryptoServiceInterface`
* `generateKeyPair(string $passphrase): KeyPairDTO`
* `sign(string $data, string $encryptedPrivateKey, string $passphrase): string`
* `verify(string $data, string $signature, string $publicKey): bool`

### `PdfSignerInterface`
* `embedVisualSignature(string $pdfPath, SignaturePositionDTO $position, string $qrCodePath): string`
* `calculatePdfHash(string $pdfPath): string`

---

## 3. Security & Key Management Architecture
1. **Passphrase Protection**: Private Key pengguna dienkripsi menggunakan AES-256-GCM dengan *key derivation function* (Argon2id / PBKDF2) yang diturunkan dari passphrase pengguna atau Master Secret Key aplikasi.
2. **Document Hash Standard**: Seluruh komputasi integritas file menggunakan algoritma `SHA-256`.
3. **Storage Isolation**:
   - File PDF Draft: `storage/app/documents/drafts/` (Private, Terproteksi)
   - File PDF Signed: `storage/app/documents/signed/` (Private, Terproteksi)
   - Temporary QR/Visuals: `storage/app/temp/` (Ephemeral)