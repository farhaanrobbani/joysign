📋 Business Rules (business-rule.md)
1. Ketentuan Umum & Pembuatan Akun Pengguna
Validasi Identitas: Setiap pengguna yang ingin melakukan penandatanganan dokumen wajib terdaftar oleh admin atau operator melalui panel (registrasi publik tidak tersedia) dan terverifikasi identitas dasarnya (seperti pencocokan NIK dan email aktif).
Kepemilikan Kunci (Keypair): Setiap akun pengguna hanya dapat memiliki satu pasang kunci aktif (Active Private/Public Keypair) yang dihasilkan melalui modul kriptografi OpenSSL pada saat aktivasi akun atau permintaan pembuatan sertifikat baru.
Passphrase Akun/Kunci: Pengguna wajib memasukkan passphrase rahasia untuk mendekripsi Private Key setiap kali melakukan proses penandatanganan dokumen (signing). Sistem tidak menyimpan passphrase dalam bentuk plain text.
2. Manajemen Dokumen & Batasan File
Format File: Dokumen yang diunggah wajib berformat PDF standar (versi 1.4 hingga 2.0). Format lain seperti DOCX atau gambar harus dikonversi terlebih dahulu ke PDF di luar sistem atau melalui modul konverter terintegrasi.
Ukuran Maksimal: Ukuran file dokumen PDF yang diunggah dibatasi maksimal 20 MB per file untuk menjaga performa rendering dan pemrosesan kriptografi.
Status Dokumen:
draft: Dokumen baru diunggah dan belum ditandatangani.
signed: Dokumen telah berhasil dibubuhi tanda tangan visual dan kriptografis.
revoked: Dokumen dibatalkan atau ditarik kembali oleh pemilik/sistem karena alasan hukum atau keamanan.
3. Aturan Penandatanganan (Signing Workflow Rules)
Koordinat Penempatan: Tanda tangan visual (QR Code) wajib memiliki koordinat posisi (X, Y), nomor halaman, lebar, dan tinggi yang valid dalam rentang dimensi halaman PDF target.
Integritas Hash: Sebelum proses penandatanganan dimulai, sistem wajib menghitung nilai Hash SHA-256 dari dokumen asli. Hash ini akan diikat bersama tanda tangan digital untuk mencegah manipulasi isi (tampering).
Non-Repudiation (Penyangkalan): Tanda tangan digital yang telah diproses menggunakan Private Key sah milik pengguna memiliki kekuatan hukum bahwa dokumen tersebut benar disetujui oleh pemilik akun yang bersangkutan, dan tidak dapat disangkal kecuali terbukti ada kebocoran kunci.
4. Ketentuan Verifikasi Publik
Akses Terbuka: Portal verifikasi dokumen dapat diakses oleh publik tanpa harus login ke dalam sistem (melalui URL unik hasil scan QR Code atau memasukkan token verifikasi 12 karakter di halaman verifikasi).
Indikator Keaslian: Sistem verifikasi publik hanya memberikan 3 status validasi akhir:
VALID: Dokumen asli, tanda tangan terverifikasi dengan Public Key, dan Hash file cocok sempurna tanpa perubahan.
INVALID / TAMPERED: Dokumen terindikasi telah diubah atau dimodifikasi isinya setelah proses penandatanganan selesai.
EXPIRED / REVOKED: Sertifikat atau dokumen sudah kedaluwarsa atau dicabut hak berlakunya.
5. Kebijakan Log Aktivitas (Audit Trail)
Pencatatan Otomatis: Setiap aksi penting (Unggah, Atur Posisi, Tandatangani, Verifikasi, Unduh) wajib dicatat secara permanen ke dalam tabel log audit beserta informasi waktu (UTC), alamat IP, dan User-Agent perangkat pelaku.
Retensi Log: Log audit tidak boleh dihapus secara bebas oleh pengguna biasa, dan hanya dapat dikelola oleh administrator sistem untuk keperluan investigasi forensik digital.
