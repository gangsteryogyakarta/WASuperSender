Task List: Pengembangan WA Blast Multi-Sender (WAHA Edition)

Phase 1: Setup & Architecture (WAHA Integration)

[ ] Environment Setup:

[ ] Setup Docker Container untuk WAHA (devlikeapro/waha) secara lokal untuk development.

[ ] Konfigurasi docker-compose.yml agar support multi-session.

[ ] Project Init: Inisialisasi Project Electron + React (atau framework pilihan).

[ ] API Handler: Implementasi Service Layer / API Client (menggunakan Axios/Fetch) untuk berkomunikasi dengan WAHA Server.

[ ] Fungsi checkServerHealth() untuk memastikan WAHA berjalan.

[ ] Database Lokal: Setup SQLite/LowDB di aplikasi Desktop untuk menyimpan konfigurasi URL WAHA dan data lokal pengguna.

Phase 2: Manajemen Sesi (Multi-Device API)

[ ] Create Session: Implementasi fungsi memanggil POST /api/sessions untuk membuat profil baru.

[ ] QR Code: Implementasi logika polling atau websocket untuk mengambil gambar QR Code (GET /api/sessions/{session}/screenshot) dan menampilkannya di UI.

[ ] Session State: Menampilkan status sesi (SCAN_QR_CODE, WORKING, FAILED) secara real-time di Dashboard.

[ ] Delete Session: Implementasi fungsi DELETE /api/sessions/{session} untuk menghapus profil.

Phase 3: Fitur Kontak & Pesan

[ ] UI Import: Halaman untuk upload file Excel/CSV.

[ ] File Parsing: Logika membaca file Excel dan mengubahnya menjadi array object JSON.

[ ] Validation (Optional): Fitur "Check Numbers" menggunakan endpoint GET /api/contacts/check-exists sebelum blasting dimulai.

[ ] Message Form: UI input pesan dengan dukungan preview variabel (Halo [Nama]).

Phase 4: Engine Pengiriman (Client-Side Logic)

[ ] Queue Manager: Membuat sistem antrian lokal di aplikasi Desktop. Antrian ini bertugas memanggil API WAHA satu per satu.

[ ] Delay Logic: Implementasi Sleep/Delay function di antara panggilan API sendText (Penting: Jangan membanjiri API server secara instan).

[ ] Media Sending: Implementasi konversi file lokal ke format yang diterima WAHA (Base64 atau upload file mechanism).

[ ] Pause/Resume: Tombol kontrol untuk menghentikan sementara loop pengiriman antrian.

Phase 5: Reporting & Webhook Handling

[ ] Monitoring:

[ ] Pilihan 1 (Sederhana): Menandai "Sent" jika respons API WAHA sukses (200 OK).

[ ] Pilihan 2 (Advanced): Menjalankan server HTTP kecil di dalam aplikasi Electron untuk menerima Webhook dari WAHA (status delivered/read).

[ ] Real-time UI: Update tabel log pengiriman saat API merespons.

[ ] Export: Fitur export hasil log ke Excel.

Phase 6: Packaging & Deployment Strategy

[ ] Bundling Strategy: Memutuskan cara mendistribusikan WAHA Engine ke user:

[ ] Option A: User harus install Docker sendiri (Dokumentasi).

[ ] Option B: Bundle WAHA binary/executable bersama installer Electron (Lebih user friendly tapi ukuran file besar).

[ ] Installer: Build installer aplikasi (Electron Builder).

[ ] Docs: Panduan instalasi dan cara menghubungkan aplikasi ke WAHA Server.
