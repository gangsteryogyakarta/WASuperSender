Product Requirements Document (PRD)

Aplikasi: WA Blast Multi-Sender Pro (WAHA Architecture)

1. Pendahuluan

1.1 Latar Belakang

Pengelolaan pengiriman pesan massal WhatsApp secara manual tidak efisien dan berisiko. Menggunakan arsitektur berbasis API menawarkan stabilitas yang lebih baik daripada implementasi langsung (direct socket) pada aplikasi desktop. Dengan memanfaatkan WAHA (WhatsApp HTTP API), aplikasi dapat memisahkan logika antarmuka (frontend) dari logika koneksi WhatsApp (engine), memungkinkan manajemen sesi yang lebih stabil dan skalabilitas yang lebih baik.

1.2 Tujuan

Membangun aplikasi desktop (Front-end Dashboard) yang berfungsi sebagai antarmuka manajemen untuk engine WAHA. Aplikasi ini memungkinkan pengguna mengelola multi-sesi, kampanye, dan kontak melalui panggilan HTTP API ke service WAHA yang berjalan di latar belakang (Docker/Local Process).

2. Target Pengguna

UMKM / Pemilik Bisnis Online

Digital Marketer

Tim Operasional Customer Service

3. Spesifikasi Fitur

3.1 Manajemen Profil (Multi-Session via API)

Create Session: Menggunakan endpoint WAHA (POST /api/sessions) untuk membuat sesi baru.

Start/Stop Session: Mengontrol status sesi (nomor WA) melalui API.

QR Code Display: Menampilkan QR Code di aplikasi desktop yang diambil dari respons API WAHA (GET /api/sessions/{session}/screenshot atau stream).

Session List: Menampilkan daftar sesi aktif yang dikelola oleh WAHA.

3.2 Manajemen Kontak

Import Data: Mendukung import file .csv dan .xlsx.

Data Mapping: Memetakan kolom file ke variabel pesan.

Contact Validation: (Opsional) Menggunakan endpoint WAHA checkNumberStatus untuk memvalidasi apakah nomor terdaftar di WA sebelum dikirim.

3.3 Komposisi Pesan

Text Editor: Input pesan dengan dukungan variabel dinamis.

Media Handling: Upload media ke lokal, lalu kirim path atau base64 melalui endpoint WAHA (POST /api/sendImage, POST /api/sendFile).

3.4 Pengaturan Pengiriman (The Blaster Logic)

Client-Side Queue: Aplikasi desktop mengelola antrian pengiriman.

Throttling: Aplikasi mengatur jeda waktu (delay) antar panggilan API untuk menghindari rate limit atau pemblokiran.

Sending Mode: Logic di sisi aplikasi untuk menentukan apakah mengirim pesan ke nomor baru atau yang sudah ada di kontak.

3.5 Laporan (Reporting)

Webhook / Polling:

Opsi A (Polling): Aplikasi secara berkala mengecek status pesan.

Opsi B (Webhook): WAHA mengirim status pengiriman (ACK) ke endpoint lokal aplikasi (jika aplikasi desktop menjalankan server kecil).

Export Report: Unduh log sukses/gagal berdasarkan respons API.

4. Arsitektur Teknis

Core Engine: WAHA (WhatsApp HTTP API) - Self-hosted (via Docker atau Binary).

Frontend/Client: Desktop App (Electron/React atau .NET).

Komunikasi: HTTP REST API (Request/Response) dan WebSocket (untuk event status real-time).

Database: SQLite (di sisi Desktop App) untuk menyimpan data kontak, riwayat kampanye, dan konfigurasi API URL.

5. Alur Pengguna (User Flow)

Setup: Pengguna memastikan Service WAHA berjalan (atau aplikasi meluncurkan container WAHA secara otomatis di background).

Koneksi: Aplikasi Desktop terhubung ke http://localhost:3000 (Default WAHA port).

Add Profile: User klik "Add Profile" -> Aplikasi panggil API startSession.

Scan: Aplikasi menampilkan QR Code dari API -> User Scan di HP.

Blast: User import Excel -> Klik Kirim -> Aplikasi melakukan iterasi (looping) mengirim request API /sendText per nomor dengan jeda waktu yang ditentukan.
