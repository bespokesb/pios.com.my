# Laporan Semakan Kod Komprehensif (Comprehensive Code Review)
## Repositori: `habibtalib/pios-my` (PIOS - Pak ITAM Online Supermarket)

**Tarikh Semakan:** 12 Oktober 2026  
**Versi Stack:** PHP 8.4 • Laravel 13 • Filament v5 • Livewire 4 • Tailwind CSS v4 • MySQL  
**Status Keseluruhan:** **LULUS CEMERLANG (Production-Ready dengan Penambahbaikan Berperingkat)**  
**Skor Kualiti Kod:** **92 / 100**

---

## 1. Ringkasan Eksekutif (Executive Summary)

Projek **PIOS (Pak ITAM Online Supermarket)** merupakan platform e-dagang dan pengurusan pejabat belakang berasaskan rakan kongsi (*partner-led back-office & marketplace*). Sistem ini menghubungkan:
1. **PIOS Owner (HQ/Admin)**
2. **Partners**
3. **Business Owners (BO)**
4. **Pelanggan & Kenalan (Customers, Leads, VIP LOS)**

Sistem ini mempunyai dua teras utama:
- **Penguatkuasaan Hirarki Akses (*Hierarchy Ownership*)**: Pengguna hanya boleh melihat, mengurus, dan menukar rekod milik hirarki sendiri.
- **Kelayakan Produk (*Amway / Non-Amway Eligibility*)**: Produk Amway dihadkan secara ketat untuk pelanggan VIP (LOS), manakala pelanggan biasa hanya dibenarkan membeli produk Non-Amway.

Semakan kod ini mendapati reka bentuk sistem dibangunkan dengan mematuhi prinsip Laravel moden, perlindungan hak akses server-side yang ketat, dan liputan ujian automatik (*automated testing*) yang mantap.

---

## 2. Analisis & Skor Penilaian Mengikut Dimensi

| Dimensi Penilaian | Skor | Status | Catatan Utama |
| :--- | :---: | :---: | :--- |
| **1. Seni Bina & Struktur (Architecture)** | 94/100 | Cemerlang | Pemisahan jelas antara Portal Perniagaan (`/business`), Konsol Filament (`/admin`), dan Kedai Awam (`/p/{product_no}`). |
| **2. Keselamatan & Hak Akses (Security & Authorization)** | 95/100 | Cemerlang | Penggunaan Laravel Policies dan Query Scopes `visibleTo()` menghalang kebocoran data hirarki di peringkat pangkalan data. |
| **3. Logik Bisnes & Integriti Data (Domain Logic)** | 93/100 | Cemerlang | Kuota had 20 produk per BO, pemisahan nombor perniagaan (`PRD...`, `BO...`) daripada ID pangkalan data, dan pengesahan yuran. |
| **4. Prestasi Pangkalan Data (Database & Eloquent)** | 88/100 | Baik | Menggunakan `SoftDeletes` untuk integriti pesanan lampau; memerlukan penambahan indeks komposit pada carian kerap. |
| **5. Pengendalian Media & Video (Media Assets)** | 92/100 | Cemerlang | Pemisahan muat naik gambar dan video (optional separate upload) serta peletakan autoplay di kedudukan paling hadapan galeri awam. |
| **6. Frontend, UI/UX & PWA** | 90/100 | Sangat Baik | Responsif 100% pada telefon bimbit & komputer meja, modal skrol lancar, sokongan PWA (manifest & icons). |
| **7. Ujian Automasi (Automated Testing)** | 95/100 | Cemerlang | 56+ fail ujian Feature merangkumi pelbagai senario pesanan, kuota, hak akses, dan API. |

---

## 3. Penemuan Terperinci Mengikut Modul

### 3.1. Penguatkuasaan Hak Akses & Keselamatan Hirarki
- **Kekuatan**:
  - `ProductPolicy`, `CustomerPolicy`, dan `BusinessOwnerPolicy` memeriksa hak milik pengguna secara tegas (`ownsProduct()`, `ownsPartnerDirectCustomer()`).
  - Model `Product` dan `Customer` dilengkapi dengan `scopeVisibleTo(Builder $query, User $user)` yang mengelakkan manipulasi ID secara terus (IDOR - Insecure Direct Object Reference).
  - Kata laluan di-hash menggunakan algoritma selamat Laravel, dan nombor telefon dinormalkan melalui `App\Support\Phone` dan pengesahan `App\Rules\MalaysianMobile`.

### 3.2. Pengurusan Produk, Kuota 20 Produk & Had Transaksi
- **Kekuatan**:
  - Kuota 20 produk per Business Owner disemak di dua lapisan:
    1. **Lapisan Policy & Controller**: Menyemak `$bo->hasReachedProductLimit()` sebelum membenarkan pendaftaran produk baharu.
    2. **Lapisan UI**: Kad kuota memaparkan penggunaan (`0/20`), amaran jika mencapai had, dan melumpuhkan butang tambah.
  - **Fungsi Padam (*Soft Delete*)**: Menghapuskan produk tanpa melanggar kekangan `order_items` (`restrictOnDelete`), dan segera mengosongkan 1 slot kuota daripada had 20 produk.
  - **Kalkulator Stok Dinamik**: Input `Stok Semasa` dan `+ Tambah Stok` mengira jumlah unit kemasukan stok baharu secara reaktif.

### 3.3. Pengasingan Muat Naik Video & Paparan Autoplay
- **Kekuatan**:
  - Video diasingkan sepenuhnya daripada gambar dalam borang pendaftaran dan edit produk (`video_file` MP4/WEBM/MOV, had 30MB).
  - Video disimpan di laluan awam selamat `products/videos/`.
  - Pada halaman paparan pelanggan `/p/{product_no}`, video diletakkan di **kedudukan pertama (indeks 0)** dalam galeri AlpineJS dengan atribut `autoplay muted loop playsinline controls` untuk pengalaman pelanggan yang optimum.

### 3.4. Transaksi & Kitaran Pesanan (Order Lifecycle)
- **Kekuatan**:
  - `ManualOrderService` dan `TransactionController` menguruskan pesanan manual dengan transaksi pangkalan data atomik (`DB::transaction`).
  - Pelanggan baharu yang membuat pesanan awam didaftarkan secara automatik sebagai `CustomerCategory::Lead` di bawah Business Owner pemilik produk.
  - Pengiraan komisen dan harga kos tidak dibocorkan pada halaman paparan pelanggan awam (disahkan melalui `PublicProductPageTest`).

---

## 4. Cadangan Penambahbaikan (Recommendations & Action Items)

### 1. Pengekstrakan Validasi ke Form Request Dedicated
- **Situasi Semasa**: `ProductController@update` dan `CustomerController@store` mengandungi peraturan validasi yang panjang di dalam method controller.
- **Cadangan**: Cipta Form Request classes:
  - `App\Http\Requests\Business\UpdateProductRequest`
  - `App\Http\Requests\Business\StoreCustomerRequest`
- **Faedah**: Memastikan kod controller kekal ramping (*skinny controllers*) dan memisahkan tanggungjawab validasi dengan logik perniagaan.

### 2. Penambahan Indeks Pangkalan Data pada Medan Pencarian Utama
- **Situasi Semasa**: Pencarian pelanggan menggunakan klausa `like` pada `hp_no_normalized`, `customer_no`, dan `kp_no`.
- **Cadangan**: Tambah indeks tunggal atau komposit dalam migration:
  ```php
  $table->index('hp_no_normalized');
  $table->index('customer_no');
  $table->index(['business_owner_id', 'status']);
  ```
- **Faedah**: Menjamin kelajuan carian pantas walaupun pangkalan data bertambah ke puluhan ribu rekod.

### 3. GitHub Pull Request & Automated Code Review Workflow
- **Situasi Semasa**: Repositori belum mempunyai templat Pull Request dan automasi semakan kod GitHub.
- **Cadangan**:
  - Wujudkan `.github/pull_request_template.md` untuk menyelaraskan setiap penyerahan kod.
  - Sediakan GitHub Action CI untuk menjalankan `vendor/bin/pint --test` dan `php artisan test` secara automatik setiap kali Pull Request dibuka.

---

## 5. Kesimpulan Semakan

Repositori `habibtalib/pios-my` mempunyai struktur kejuruteraan perisian yang kukuh, reka bentuk UI moden yang sangat teliti, dan penguatkuasaan hak keselamatan hirarki yang berkesan. Dengan melaksanakan cadangan penambahbaikan di atas, projek ini berada pada tahap kematangan yang tinggi untuk skala pengeluaran (*production scale*).
