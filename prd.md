# Product Requirements Document (PRD)

# Nama Produk

**ceknomor.id*

Tagline:

> Cek Nomor, Cek Rekening, Hindari Penipuan Online.

---

# 1. Executive Summary

ceknomor.id adalah platform berbasis website (Mobile First) yang memungkinkan masyarakat Indonesia melakukan pengecekan:

* Nomor Telepon
* Nomor Rekening Bank Indonesia

untuk mengetahui apakah pernah dilaporkan sebagai:

* Penipu
* Spam
* Scammer
* Debt Collector
* Telemarketing
* Pinjaman Online
* dan kategori lainnya.

Database akan berkembang secara otomatis (compounding database) berdasarkan kontribusi pengguna yang melakukan laporan dan memberikan komentar anonim.

Target utama adalah menjadi database nomor telepon dan rekening terbesar di Indonesia.

---

# 2. Tujuan Produk

Membantu masyarakat Indonesia agar:

* Terhindar dari penipuan online
* Mengecek identitas nomor sebelum melakukan transaksi
* Mengecek rekening sebelum transfer
* Mengecek nomor yang menghubungi mereka
* Berkontribusi membangun database anti penipuan nasional

---

# 3. Target Market

### Primary

Masyarakat Indonesia

Usia:

18–45 tahun

Aktif menggunakan:

* WhatsApp
* Shopee
* Tokopedia
* Facebook
* Instagram
* TikTok
* Marketplace

---

### Secondary

Pelaku UMKM

Seller Marketplace

Dropshipper

Reseller

Customer Service

Admin Marketplace

Freelancer

---

# 4. Problem Statement

Saat ini masyarakat sulit mengetahui apakah:

Nomor telepon yang menghubungi mereka adalah penipu.

Rekening tujuan transfer pernah digunakan melakukan penipuan.

Belum ada database Indonesia yang:

* lengkap
* cepat
* berbasis komunitas
* mudah digunakan

---

# 5. Value Proposition

Dalam hitungan kurang dari satu detik pengguna dapat mengetahui apakah:

Nomor tersebut aman.

Nomor tersebut pernah dilaporkan.

Rekening pernah dipakai penipuan.

Komentar pengguna lain.

Skor keamanan.

---

# 6. Target KPI

Tahun Pertama

1 juta nomor telepon

300 ribu rekening

500 ribu user

5 juta pencarian

100 ribu laporan

---

# 7. Fitur Utama

## A. Search Nomor Telepon

Input:

08123456789

Output:

Nama (jika tersedia dari kontribusi pengguna atau sumber yang sah)

Label keamanan

Jumlah laporan

Jumlah komentar

Kategori

Tanggal laporan terakhir

---

## B. Search Rekening

Support seluruh bank Indonesia.

Contoh:

BCA

BRI

BNI

Mandiri

CIMB

Permata

BSI

Danamon

BTN

Bank Digital Indonesia

dll.

Output:

Nama pemilik rekening (jika tersedia dari kontribusi pengguna atau integrasi yang sah)

Label keamanan

Jumlah laporan

Komentar

Kategori

---

## C. Community Report

User dapat melaporkan:

Nomor Telepon

Nomor Rekening

Kategori:

Penipuan

Spam

Debt Collector

Judi Online

Pinjaman Online

Robot Trading

Scam Marketplace

Penjual Fiktif

Lainnya

---

## D. Anonymous Comment

Pengguna dapat memberi komentar anonim.

Contoh:

"Hati-hati, meminta transfer DP."

"Nomor mengaku polisi."

"Spam pinjaman online."

---

## E. Voting

Komentar dapat diberi:

Helpful

Tidak Membantu

---

## F. Riwayat

Melihat histori laporan.

---

## G. Trending

Nomor paling banyak dicari hari ini.

Rekening paling banyak dicari.

---

## H. Top Scam

Top 100 nomor paling banyak dilaporkan.

---

# 8. Sistem Score

## Hijau

0 laporan

Status:

AMAN

---

## Kuning

1–2 laporan

Status:

WASPADA

---

## Oranye

3–5 laporan

Status:

HATI-HATI

---

## Merah

Lebih dari 5 laporan

Status:

BERBAHAYA

> **Catatan Produk:** Sebaiknya skor tidak hanya berdasarkan jumlah laporan. Gunakan sistem pembobotan untuk mengurangi laporan palsu, misalnya mempertimbangkan reputasi pelapor, usia akun, dan validasi laporan. Hal ini akan membuat sistem lebih adil dan mengurangi potensi penyalahgunaan.

---

# 9. User Flow

Landing Page

↓

Search

↓

Hasil

↓

Login Google (jika ingin memberi komentar atau laporan)

↓

Tambah Laporan

↓

Database Bertambah

↓

Search Berikutnya Semakin Lengkap

---

# 10. Login

Menggunakan:

Google OAuth

Tujuan:

Mengurangi spam akun

Satu akun Google

Satu identitas pengguna

---

# 11. Integrasi Google Contacts

Saat login, pengguna dapat **memberikan izin opsional** untuk membaca kontak Google mereka. Aplikasi kemudian dapat mencocokkan nomor yang dicari dengan kontak milik pengguna sehingga nama kontak pribadi dapat ditampilkan **hanya kepada pengguna tersebut**.

> **Catatan Penting:** Kebijakan Google membatasi penggunaan Google Contacts. Nama kontak milik pengguna **tidak boleh dijadikan database publik** atau dibagikan kepada pengguna lain tanpa dasar hukum dan persetujuan yang sesuai. Fitur ini harus dirancang agar hanya digunakan secara privat di akun pengguna.

---

# 12. Search Result

Contoh

08123456789

Status

🟢 Aman

atau

🟡 Waspada

atau

🟠 Hati-hati

atau

🔴 Berbahaya

Jumlah laporan

Komentar

Kategori

Terakhir dilaporkan

---

# 13. Landing Page

Hero Section

"Cek Nomor Telepon & Rekening Sebelum Terlambat"

Search Box

Nomor Telepon

Nomor Rekening

CTA

Cek Sekarang

Trending

Nomor Terpopuler

Rekening Terpopuler

Artikel SEO

---

# 14. SEO Strategy

Landing Page dioptimalkan untuk kata kunci seperti:

cek nomor telepon

cek nomor penipu

cek rekening penipu

cek rekening online

nomor scam indonesia

nomor spam

nomor debt collector

cek nomor WA

cek rekening BCA

cek rekening BRI

cek rekening Mandiri

cara cek rekening penipu

nomor telepon penipu

database penipu online

Lalu buat ribuan halaman indeks berdasarkan:

Nomor Telepon

Nomor Rekening

Kategori

Bank

Artikel Edukasi

FAQ

---

# 15. Monetisasi

Google AdSense

Banner Landing

Banner Hasil Search

Native Ads

Sponsored Listing (opsional)

Premium API (masa depan)

---

# 16. Teknologi

Frontend

HTML5

CSS

JavaScript

Mobile First

Backend

PHP Native

MySQL

Redis

Nginx

Linux

---

# 17. Database

MySQL

Menggunakan:

INDEX

Composite Index

FULLTEXT

Redis Cache

Query Cache

Prepared Statement

Pagination

---

# 18. Optimasi Search

Flow

User Search

↓

Redis Check

↓

Jika ada

Return

↓

Jika tidak

MySQL

↓

Cache Redis

↓

Return

Target Response

<300 ms (cache)

<1 detik (cold query)

---

# 19. Struktur Database (High-Level)

Tabel utama:

* users
* phone_numbers
* bank_accounts
* reports
* comments
* report_categories
* votes
* search_history
* banks
* user_reputation
* audit_logs

Index utama:

* phone_number
* bank_code + account_number
* score
* last_reported_at
* report_count

---

# 20. Keamanan

Google Login

Rate Limit

CSRF

XSS Protection

SQL Injection Protection

Captcha

Audit Log

IP Tracking

Device Fingerprint (opsional)

---

# 21. Moderasi

AI Spam Detection

Report Abuse

Blacklist Kata Kasar

Moderator Dashboard

Auto Hidden Comment

---

# 22. Dashboard Admin

Statistik

User

Komentar

Laporan

Bank

Nomor

Rekening

Kategori

Banner Ads

SEO

---

# 23. Roadmap

Phase 1

Search Nomor

Search Rekening

Google Login

Komentar

Report

Phase 2

Dashboard Admin

SEO

Redis

Trending

Top Scam

Phase 3

AI Moderation

API

Mobile App

Chrome Extension

---

# 24. Risiko & Kepatuhan

Karena aplikasi memuat laporan dari pengguna mengenai nomor telepon dan rekening, perlu disiapkan:

* Kebijakan privasi yang jelas.
* Syarat dan ketentuan penggunaan.
* Mekanisme pelaporan penyalahgunaan (dispute/take-down) bagi pihak yang merasa dirugikan.
* Moderasi untuk mengurangi fitnah atau pencemaran nama baik.
* Kepatuhan terhadap UU Perlindungan Data Pribadi (UU PDP) dan regulasi Indonesia lainnya.

---

# 25. Future Vision

Menjadi platform nomor 1 di Indonesia untuk:

* Cek Nomor Telepon
* Cek Rekening Bank
* Database Anti Penipuan
* Community Driven Scam Intelligence

dengan jutaan data yang terus bertambah melalui kontribusi komunitas dan sistem moderasi yang menjaga kualitas informasi.
# Penambahan Fitur PRD — Community Reputation & Review System

## 26. Community Reputation System

Selain laporan anonim, aplikasi menyediakan sistem identitas publik sehingga pengguna dapat membangun reputasi sebagai kontributor terpercaya.

Tujuan:

* Meningkatkan kepercayaan terhadap informasi.
* Mengurangi laporan palsu.
* Memberikan bobot lebih besar kepada kontributor aktif.
* Membangun komunitas anti-penipuan Indonesia.

---

# 27. Jenis Kontribusi User

Setiap pengguna dapat melakukan:

* Memberikan review pada nomor telepon.
* Memberikan review pada rekening bank.
* Memberikan komentar.
* Memberikan bukti pendukung (opsional, dengan penyensoran data sensitif).
* Memberikan vote "Helpful" pada review orang lain.
* Melaporkan review yang melanggar aturan.

---

# 28. Mode Komentar

Pengguna dapat memilih salah satu dari dua mode saat mengirimkan kontribusi.

### Mode Anonim

Ditampilkan sebagai:

> Pengguna Anonim

Cocok bagi pengguna yang ingin menjaga privasi.

---

### Mode Publik

Ditampilkan sebagai:

* Nama Profil
* Foto Profil Google (opsional)
* Badge Kontributor
* Jumlah Kontribusi
* Tingkat Kepercayaan (Trust Score)

Contoh:

⭐⭐⭐⭐⭐

Andi Pratama

Top Contributor

152 Kontribusi

Trust Score: 96%

---

# 29. Review System

Setiap nomor telepon maupun rekening memiliki halaman review.

Contoh:

08123456789

★★★★☆

4.7 / 5

Berdasarkan 328 review

Review terbaru:

★★★★★

"Seller terpercaya, transaksi lancar."

— Andi

★★★★★

"Sudah transaksi 5 kali."

— Budi

★☆☆☆☆

"Meminta transfer DP lalu menghilang."

— Anonim

---

# 30. Agregasi Penilaian Komunitas

Setiap nomor memiliki ringkasan statistik.

Contoh:

Aman menurut komunitas

92%

Berisiko

8%

Total Review

1.248

Total Komentar

2.451

Total Laporan

18

Total Pencarian

98.120

Terakhir dicek

2 menit lalu

---

# 31. Trust Score Kontributor

Setiap pengguna memiliki Trust Score (0–100) berdasarkan kualitas kontribusinya.

Faktor yang dapat memengaruhi Trust Score:

* Usia akun.
* Jumlah laporan yang tervalidasi.
* Jumlah review yang dinilai "Helpful".
* Frekuensi laporan yang terbukti tidak valid.
* Aktivitas positif secara konsisten.

Contoh:

Trust Score 98

Top Contributor

Trust Score 82

Trusted Member

Trust Score 55

Member

Trust Score 30

New Member

Semakin tinggi Trust Score, semakin besar bobot kontribusinya dalam perhitungan skor keamanan.

---

# 32. Badge Kontributor

Badge diberikan sebagai bentuk penghargaan.

Contoh:

🥇 Top Contributor

🥈 Senior Member

🥉 Trusted Member

⭐ Verified User

🛡 Scam Hunter

🏦 Banking Expert

📱 Phone Expert

Badge dapat diperoleh berdasarkan kontribusi dan kualitas laporan.

---

# 33. Skor Keamanan Berbasis Agregasi

Skor keamanan tidak hanya ditentukan oleh jumlah laporan, tetapi juga oleh beberapa sinyal.

Komponen yang dapat dipertimbangkan:

* Jumlah laporan.
* Trust Score pelapor.
* Jumlah review positif.
* Jumlah review negatif.
* Persentase review yang dinilai membantu.
* Kecepatan pertambahan laporan.
* Umur laporan (laporan lama dapat memiliki bobot yang lebih rendah jika tidak ada kejadian baru).
* Hasil moderasi.

Contoh formula konseptual:

Security Score =
40% Valid Reports +
25% Trusted Reviews +
15% Helpful Votes +
10% Report Recency +
10% Community Reputation

---

# 34. Ringkasan Komunitas (Community Insight)

Pada halaman hasil pencarian ditampilkan ringkasan otomatis.

Contoh:

### Ringkasan Komunitas

* 89% pengguna menyatakan nomor ini aman.
* 11% pengguna pernah mengalami spam.
* Mayoritas laporan berasal dari telemarketing.
* Belum ditemukan laporan penipuan finansial.

atau

### Ringkasan Komunitas

* 87% pengguna melaporkan indikasi penipuan.
* Banyak laporan mengenai permintaan transfer uang.
* Nomor sering berganti identitas.
* Disarankan tidak melakukan transaksi.

Ringkasan ini dapat dibuat menggunakan AI berdasarkan review dan laporan yang telah dimoderasi.

---

# 35. Profil Kontributor

Setiap pengguna memiliki halaman profil publik yang menampilkan:

* Nama pengguna.
* Badge.
* Trust Score.
* Jumlah review.
* Jumlah laporan tervalidasi.
* Helpful Vote yang diterima.
* Bergabung sejak.
* Aktivitas terbaru.

Tujuannya agar pengguna lain dapat menilai kredibilitas kontributor.

---

# 36. Leaderboard

Menampilkan kontributor terbaik.

Kategori:

* Mingguan.
* Bulanan.
* Tahunan.
* Sepanjang waktu.

Parameter:

* Helpful Vote.
* Trust Score.
* Jumlah laporan tervalidasi.
* Jumlah review berkualitas.

Leaderboard mendorong partisipasi aktif dan meningkatkan kualitas data komunitas.

---

# 37. Dampak Terhadap SEO

Setiap halaman nomor atau rekening akan memiliki konten yang terus bertambah (user-generated content), seperti:

* Review baru.
* Komentar baru.
* Ringkasan komunitas.
* Statistik terbaru.
* Aktivitas pencarian.

Konten yang terus diperbarui membantu meningkatkan peluang halaman untuk tetap relevan di mesin pencari dan memperkaya informasi bagi pengguna.
# 38. Admin Panel (Super Admin Dashboard)

## Tujuan

Admin Panel berfungsi sebagai pusat kontrol untuk:

* Memantau pertumbuhan platform.
* Mengelola laporan dan moderasi.
* Mengelola pengguna.
* Mengelola iklan.
* Memantau performa server.
* Melihat analitik bisnis secara real-time.

---

# Dashboard Utama

Saat admin login, ditampilkan ringkasan KPI.

### Statistik Hari Ini

* Total Pengunjung Hari Ini
* User Login Hari Ini
* User Baru
* Total Pencarian
* Total Nomor Dicek
* Total Rekening Dicek
* Total Laporan Baru
* Total Review Baru
* Total Komentar Baru
* Total Nomor Baru
* Total Rekening Baru

---

### Statistik Keseluruhan

* Total User
* Total Nomor Telepon
* Total Rekening
* Total Laporan
* Total Review
* Total Komentar
* Total Pencarian
* Total Halaman Terindeks SEO
* Total Bank Indonesia yang Didukung

---

# Grafik Pertumbuhan

Grafik harian, mingguan, bulanan, dan tahunan untuk:

* Pertumbuhan User
* Pertumbuhan Database Nomor
* Pertumbuhan Database Rekening
* Pertumbuhan Review
* Pertumbuhan Komentar
* Pertumbuhan Laporan
* Pertumbuhan Pencarian
* Pertumbuhan Traffic
* Pertumbuhan Pendapatan Iklan

---

# Live Activity

Realtime Feed

Contoh:

Andi mencari:

08123456789

Budi melaporkan rekening BCA

Siti memberikan review bintang 5

Rudi menambahkan komentar

Anonim melaporkan spam

Semua aktivitas tampil secara real-time.

---

# Statistik Search

Top 100 Nomor Paling Dicari

Top 100 Rekening Paling Dicari

Top Keyword

Pencarian gagal (nomor belum ada)

Keyword yang sedang tren

Pencarian berdasarkan bank

Pencarian berdasarkan provinsi (jika tersedia)

---

# Moderasi Konten

Admin dapat:

* Menyetujui laporan
* Menolak laporan
* Menghapus komentar
* Menyembunyikan review
* Mengunci halaman nomor
* Menggabungkan data duplikat
* Menandai laporan palsu
* Mengelola sengketa dari pihak yang dilaporkan

---

# Manajemen User

Data pengguna:

* Nama
* Email
* Foto Profil
* Tanggal Bergabung
* Login Terakhir
* Trust Score
* Badge
* Jumlah Review
* Jumlah Laporan
* Status Akun

Aksi:

* Suspend
* Ban
* Reset Trust Score
* Verifikasi
* Edit Badge

---

# Manajemen Nomor

Admin dapat:

* Edit data nomor
* Merge nomor duplikat
* Edit skor
* Hapus nomor
* Lock nomor
* Tambah catatan internal
* Melihat histori perubahan

---

# Manajemen Rekening

Admin dapat:

* Edit rekening
* Merge rekening
* Ubah status
* Lock rekening
* Hapus rekening
* Lihat histori laporan

---

# Analitik Komunitas

Menampilkan:

* User paling aktif
* Top Contributor
* Review terbanyak
* Helpful Vote terbanyak
* Trust Score tertinggi
* Spammer terbanyak
* Akun mencurigakan

---

# Dashboard SEO

Menampilkan:

* Total Halaman Terindeks
* Halaman Belum Terindeks
* Keyword Teratas
* Organic Visitor
* CTR
* Impression
* Halaman dengan Traffic Terbesar
* Halaman dengan Bounce Rate Tertinggi

---

# Dashboard Iklan

Statistik iklan:

* Total Impression Banner
* Total Klik
* CTR
* RPM
* Pendapatan Hari Ini
* Pendapatan Bulan Ini
* Pendapatan Tahun Ini
* Posisi Banner Terbaik

Mendukung beberapa jaringan iklan (misalnya Google AdSense atau jaringan lain).

---

# Dashboard Server

Monitoring:

* CPU Usage
* RAM Usage
* Disk Usage
* MySQL Connections
* Redis Memory
* Redis Hit Ratio
* PHP-FPM Worker
* Queue
* Error Log
* Slow Query
* Cache Hit
* Cache Miss

Status server ditampilkan dengan indikator warna.

---

# Database Analytics

Statistik:

* Total Database
* Ukuran Database
* Growth Harian
* Growth Bulanan
* Nomor Baru
* Rekening Baru
* Komentar Baru
* Review Baru
* Laporan Baru

---

# Fraud Detection Dashboard

Mendeteksi aktivitas mencurigakan:

* Banyak laporan dari IP yang sama
* Banyak akun menggunakan perangkat yang sama
* Spam komentar
* Bot Activity
* Review massal
* Voting tidak wajar
* Percobaan manipulasi skor

Admin dapat langsung mengambil tindakan.

---

# Sistem Notifikasi

Admin menerima notifikasi untuk:

* Lonjakan laporan pada satu nomor
* Nomor yang tiba-tiba viral
* Rekening dengan banyak laporan baru
* Spam attack
* Server down
* Redis bermasalah
* Backup gagal

---

# Banner Management

Admin dapat:

* Menambah banner
* Mengatur posisi banner
* Mengatur jadwal tayang
* Menentukan target halaman
* Mengaktifkan/menonaktifkan banner
* Memantau performa tiap banner

Posisi banner:

* Landing Page
* Halaman Hasil Pencarian
* Halaman Artikel
* Sidebar (desktop)
* Footer

---

# CMS (Content Management System)

Admin dapat mengelola:

* Artikel edukasi
* FAQ
* Halaman statis
* Tips anti penipuan
* Daftar modus penipuan terbaru
* Informasi bank di Indonesia

---

# Backup & Recovery

Fitur:

* Backup Database Manual
* Backup Otomatis Terjadwal
* Restore Database
* Download Backup
* Riwayat Backup
* Notifikasi jika backup gagal

---

# Log Aktivitas Admin

Seluruh aktivitas admin dicatat, termasuk:

* Login
* Logout
* Edit data
* Hapus data
* Moderasi komentar
* Moderasi laporan
* Perubahan konfigurasi
* Pengelolaan banner

Setiap log menyimpan:

* Waktu
* Admin
* IP Address
* Device
* Aktivitas
* Data yang diubah

---

# Role & Permission

Hak akses dibagi menjadi beberapa peran:

### Super Admin

Akses penuh ke seluruh sistem.

### Administrator

Mengelola operasional dan konfigurasi.

### Moderator

Mengelola laporan, komentar, dan review.

### SEO Manager

Mengelola artikel, landing page, dan optimasi SEO.

### Customer Support

Menangani pertanyaan pengguna dan sengketa.

### Finance / Ads Manager

Mengelola banner dan memantau pendapatan iklan.

Setiap peran memiliki hak akses (permission) yang dapat dikonfigurasi secara granular.
