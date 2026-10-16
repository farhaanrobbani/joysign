# 🤖 AI Agents & Developer Workflows (`AGENTS.md`)

Dokumen ini mendefinisikan peran AI Coding Agents (seperti Cursor, GitHub Copilot, Claude Code, dll.) dalam membantu pengembangan aplikasi Web TTE berbasis Laravel 13 & PHP 8.5.

---

## 1. Agent Roles & Responsibilities

| Agent / Context | Role / Focus Area | Rules & Constraints |
| :--- | :--- | :--- |
| **Architect Agent** | Merancang struktur class, interface, dan arsitektur data. | Harus mematuhi `ARCHITECTURE.md` dan `DATABASE.md`. Gunakan strict typing PHP 8.5. |
| **Crypto & Security Agent** | Implementasi OpenSSL, Hashing, dan Enkripsi Key. | Kunci Private TIDAK BOHLEH dicetak ke log/console. Wajib error handling ketat. |
| **PDF Processing Agent** | Manipulasi PDF (FPDI/TCPDF), rendering visual & QR Code. | Pastikan koordinat PDF ($X, Y$) akurat dalam satuan $mm$ atau $pt$. |
| **Feature Agent** | Implementasi Controller, Livewire/Inertia Component, Form Requests. | Gunakan fitur modern PHP 8.5 (Asymmetric Visibility, Property Hooks jika relevan, Readonly). |

---

## 2. Coding Standards for AI Agents
1. **PHP 8.5 Standard**:
   - Selalu sertakan `declare(strict_types=1);` di setiap file PHP.
   - Gunakan Type Hints dan Return Type Declarations secara eksplisit.
   - Manfaatkan constructor property promotion dan fitur terbaru PHP 8.x/8.5.
2. **Laravel 13 Standard**:
   - Manfaatkan Laravel Form Requests untuk validasi input.
   - Gunakan Custom Service Provider untuk mengikat (*bind*) interface kriptografi.
   - Terapkan Primary Key bertipe `ULID` / `UUID`.

---

## 3. Step-by-Step Agent Prompts Execution

```text
[Prompt Phase 1: Database Setup]
"Buatkan file migrations dan Models Laravel 13 sesuai spesifikasi DATABASE.md. Gunakan HasUlids trait, Mass Assignment protection ($fillable/$guarded), dan definisikan relasi Eloquent secara lengkap."

[Prompt Phase 2: Crypto Service]
"Implementasikan App\Services\Crypto\OpenSslService yang mengimplementasikan CryptoServiceInterface. Sertakan fungsi generateKeyPair(), signHash(), dan verifySignature(). Terapkan strict typing PHP 8.5."

[Prompt Phase 3: PDF Signing Engine]
"Buatkan App\Services\Pdf\PdfSignerService menggunakan FPDI/TCPDF. Service ini harus dapat menerima file PDF, koordinat X Y, halaman, QR Code, dan logo perusahaan (opsional), lalu menghasilkan file PDF baru ter-embed visual signature."
