<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Default Pengaturan Website
|--------------------------------------------------------------------------
| Nilai bawaan untuk setiap key setting. Admin dapat mengubahnya lewat
| halaman Pengaturan; nilai tersimpan (DB) menang atas default ini.
| Ikon (path SVG) tidak diedit & tetap berada di view welcome.blade.php.
*/

return [

    'defaults' => [
        // Brand & SEO
        'site.name' => env('APP_NAME', 'TTE Web'),
        'site.tagline' => 'Tanda Tangan Elektronik Kriptografis yang aman & sah.',
        'seo.title' => env('APP_NAME', 'TTE Web').' — Tanda Tangan Elektronik Aman & Sah',
        'seo.description' => 'Platform tanda tangan elektronik (TTE) berbasis kriptografi: tanda tangan dokumen, multi penandatangan, sertifikat digital, dan portal verifikasi publik.',

        // Hero
        'landing.hero.badge' => 'Tanda Tangan Elektronik Kriptografis',
        'landing.hero.title' => 'Tanda tangani dokumen dengan',
        'landing.hero.title_accent' => 'aman & sah',
        'landing.hero.subtitle' => 'Menandatangani dokumen secara elektronik dengan kriptografi kelas industri, mengelola multi penandatangan, serta memverifikasi keaslian dokumen kapan saja — tanpa kertas.',
        'landing.hero.stat1_value' => 'RSA-2048',
        'landing.hero.stat1_label' => 'Kunci digital',
        'landing.hero.stat2_value' => '256-bit',
        'landing.hero.stat2_label' => 'Enkripsi AES',
        'landing.hero.stat3_value' => '100%',
        'landing.hero.stat3_label' => 'Audit trail',

        // Fitur
        'landing.fitur.badge' => 'Fitur Unggulan',
        'landing.fitur.title' => 'Semua yang Anda butuhkan untuk TTE',
        'landing.fitur.subtitle' => 'Dari penandatanganan hingga verifikasi, dalam satu platform yang terintegrasi.',
        'landing.fitur.1.title' => 'Tanda Tangan Kriptografis',
        'landing.fitur.1.desc' => 'Setiap tanda tangan dihitung dari hash dokumen dengan kunci privat RSA-2048 — tidak dapat dipalsukan.',
        'landing.fitur.2.title' => 'Multi Penandatangan',
        'landing.fitur.2.desc' => 'Kirim dokumen ke beberapa penandatangan berurutan atau paralel, lengkap dengan pelacakan progres.',
        'landing.fitur.3.title' => 'Sertifikat Digital',
        'landing.fitur.3.desc' => 'Kelola sertifikat digital per pengguna dengan siklus hidup aktif, kedaluwarsa, dan penarikan.',
        'landing.fitur.4.title' => 'Portal Verifikasi Publik',
        'landing.fitur.4.desc' => 'Siapa pun dapat memeriksa keaslian dokumen lewat QR Code atau token — tanpa perlu akun.',
        'landing.fitur.5.title' => 'Audit Trail Lengkap',
        'landing.fitur.5.desc' => 'Setiap aksi — unggah, tanda tangan, verifikasi, penarikan — tercatat rapi dan dapat diaudit.',
        'landing.fitur.6.title' => 'Manajemen Perusahaan & Peran',
        'landing.fitur.6.desc' => 'Kelola tim dengan peran Admin, Operator, dan Penandatangan beserta dokumen perusahaan.',

        // Cara Kerja
        'landing.steps.badge' => 'Cara Kerja',
        'landing.steps.title' => 'Empat langkah, dokumen selesai',
        'landing.steps.1.title' => 'Daftar & Aktifkan',
        'landing.steps.1.desc' => 'Buat akun, lalu aktifkan sertifikat digital Anda dengan kunci privat.',
        'landing.steps.2.title' => 'Unggah Dokumen',
        'landing.steps.2.desc' => 'Unggah PDF yang ingin ditandatangani dan tentukan posisi tanda tangan.',
        'landing.steps.3.title' => 'Tanda Tangan',
        'landing.steps.3.desc' => 'Tandatangani sendiri atau kirim ke beberapa penandatangan.',
        'landing.steps.4.title' => 'Bagikan & Verifikasi',
        'landing.steps.4.desc' => 'Sebarkan dokumen ter-sign dan biarkan pihak lain memverifikasi keasliannya.',

        // Portal Verifikasi
        'landing.verify.badge' => 'Portal Verifikasi',
        'landing.verify.title' => 'Cek keaslian dokumen dalam hitungan detik',
        'landing.verify.subtitle' => 'Masukkan token dari QR Code pada dokumen untuk memeriksa status tanda tangannya.',

        // Keamanan
        'landing.security.badge' => 'Keamanan & Kepatuhan',
        'landing.security.title' => 'Dibangun di atas kriptografi standar industri',
        'landing.security.subtitle' => 'Setiap lapisan dirancang untuk menjaga kerahasiaan, keutuhan, dan non-repudiasi dokumen Anda.',
        'landing.security.1.title' => 'RSA-2048',
        'landing.security.1.desc' => 'Pasangan kunci asimetris untuk tanda tangan kriptografis.',
        'landing.security.2.title' => 'AES-256-GCM',
        'landing.security.2.desc' => 'Enkripsi ber-authenticated untuk data sensitif.',
        'landing.security.3.title' => 'Argon2id',
        'landing.security.3.desc' => 'Hashing password tahan brute-force.',
        'landing.security.4.title' => 'SHA-256',
        'landing.security.4.desc' => 'Integritas dokumen lewat hash kriptografis.',
        'landing.security.5.title' => 'Audit Trail',
        'landing.security.5.desc' => 'Jejak log yang lengkap dan tidak dapat dimanipulasi.',
        'landing.security.6.title' => 'Enkripsi at-rest',
        'landing.security.6.desc' => 'Berkas & kunci disimpan terenkripsi di server.',

        // FAQ
        'landing.faq.badge' => 'FAQ',
        'landing.faq.title' => 'Pertanyaan yang sering diajukan',
        'landing.faq.1.q' => 'Apa itu Tanda Tangan Elektronik (TTE)?',
        'landing.faq.1.a' => 'TTE adalah cara menandatangani dokumen secara digital menggunakan kriptografi. Tanda tangan elektronik yang benar memiliki kekuatan hukum setara tanda tangan basah, karena mampu membuktikan identitas penandatangan dan keutuhan dokumen.',
        'landing.faq.2.q' => 'Apakah tanda tangan di sini sah secara hukum?',
        'landing.faq.2.a' => 'Ya. Tanda tangan elektronik yang menggunakan sertifikat digital dan kriptografi kriptografis memenuhi syarat pembuktian elektronik sesuai peraturan yang berlaku. Setiap dokumen memiliki jejak audit yang dapat diverifikasi.',
        'landing.faq.3.q' => 'Bagaimana cara memverifikasi dokumen?',
        'landing.faq.3.a' => 'Buka halaman Verifikasi, lalu masukkan token dari QR Code pada dokumen. Sistem akan memeriksa keaslian file, validitas sertifikat, dan status dokumen (VALID, TAMPERED, REVOKED, atau EXPIRED).',
        'landing.faq.4.q' => 'Siapa saja yang bisa menggunakan aplikasi ini?',
        'landing.faq.4.a' => 'Tiga peran: Admin (mengelola pengguna & sistem), Operator (mengelola perusahaan dan penandatangan), serta Penandatangan (menandatangani dokumen). Pihak luar dapat memverifikasi dokumen tanpa perlu akun.',
        'landing.faq.5.q' => 'Apakah dokumen saya aman?',
        'landing.faq.5.a' => 'Dokumen dienkripsi saat disimpan, hash kriptografis menjamin keutuhannya, dan kunci privat tidak pernah dibagikan. Setiap aksi tercatat dalam audit trail yang lengkap.',

        // CTA penutup
        'landing.cta.title' => 'Siap beralih ke tanda tangan digital?',
        'landing.cta.subtitle' => 'Mulai hari ini — buat akun, aktifkan sertifikat, dan tanda tangani dokumen pertama Anda dalam beberapa menit.',
    ],

];
