# 🚀 Derslig Digital Wallet API

Derslig Dijital Cüzdan API, modern finansal uygulamalar için tasarlanmış yüksek güvenlikli, ölçeklenebilir ve esnek bir cüzdan yönetim sistemidir. Bu proje, karmaşık işlem boru hatları (Transaction Pipelines), kademeli komisyon stratejileri ve gelişmiş dolandırıcılık tespit mekanizmalarını modern mimari desenlerle birleştirir.

---

## 🏗 Mimari Yapı (Architectural Overview)

Proje, sürdürülebilirlik ve test edilebilirlik prensipleri doğrultusunda şu mimari desenler üzerine inşa edilmiştir:

- **Pipeline Pattern:** İşlem akışları (Transaction Flow) birbirine bağlı "pipe"lar üzerinden yönetilir. Bu sayede doğrulama, bakiye kontrolü, komisyon hesaplama ve fraud kontrolü modüler bir yapıda yürütülür.
- **Strategy Pattern:** Komisyon hesaplamaları, miktara göre değişen stratejiler (`LowAmountFeeStrategy`, `MediumAmountFeeStrategy`, `HighAmountFeeStrategy`) üzerinden dinamik olarak seçilir.
- **Repository & Service Pattern:** Veritabanı işlemleri ve iş mantığı (Business Logic) birbirinden tamamen soyutlanmıştır.
- **DTO (Data Transfer Objects):** Katmanlar arası veri transferi, tip güvenli nesneler üzerinden yapılır.

### İşlem Akış Diyagramı (Pipeline)
`TransactionService` -> `CheckInsufficientBalance` -> `CheckDailyLimit` -> `CalculateFee` -> `FraudCheck` -> `Database Commit`

---

## 🛠 Teknoloji Yığını

- **Backend:** Laravel 12 (PHP 8.5+)
- **Authentication:** Laravel Sanctum (Token-based)
- **Database:** MySQL 8.0
- **Dökümantasyon:** OpenAPI/Swagger (L5-Swagger)
- **Containerization:** Laravel Sail (Docker)
- **Test:** PHPUnit & Xdebug Coverage

---

## 🚀 Hızlı Kurulum

### 1. Docker ile Kurulum (Önerilen)
Sail kullanarak projeyi hızlıca ayağa kaldırabilirsiniz:

```bash
# Çevresel değişkenleri ayarla
cp .env.example .env

# Sail'i başlat
./vendor/bin/sail up -d

# Uygulama anahtarını oluştur ve veritabanını hazırla
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

### 2. Manuel Kurulum
```bash
composer install
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

---

## 🛡 Dolandırıcılık (Fraud) Tespiti ve Kurallar

Sistem, şüpheli işlemleri gerçek zamanlı olarak takip eden ve `suspicious_activities` tablosuna loglayan bir motora sahiptir.

| Kural | Kod Adı | Limit / Detay |
| :--- | :--- | :--- |
| **Hız Kontrolü** | `velocity_limit_exceeded` | Son 60 dk içinde 10 farklı alıcıya transfer yapılması. |
| **Gece İşlemi** | `night_transaction_limit` | 00:00 - 06:00 saatleri arasında 1.000 (TRY/birim) üzeri işlem. |
| **Yeni Hesap** | `new_account_high_amount` | Hesap açılışından sonraki ilk 24 saat içinde 500 birim üzeri işlem. |

*Not: Dolandırıcılık tespit edilen bir işlemde kullanıcının cüzdanı otomatik olarak **BLOCKED** statüsüne alınır.*

---

## 💰 Dinamik Komisyon ve Limit Sistemi

Tüm limitler ve komisyon oranları `configurations` tablosu üzerinden dinamik olarak yönetilir:

- **Komisyon Kademeleri:**
  - 0 - 1.000: Sabit Ücret (Örn: 2.00)
  - 1.001 - 10.000: Yüzdelik Oran (Örn: %0.5)
  - 10.001+: Sabit + Düşük Yüzdelik Oran (Örn: 2.00 + %0.3)
- **Günlük Limitler:** Para birimi bazlı günlük toplam transfer limitleri.

---

## ⌨️ CLI Komutları (Artisan)

Proje ile birlikte gelen özel komutlar ve örnek kullanım çıktıları:

### 1. Admin Kullanıcısı Oluşturma
Sistem için manuel olarak admin yetkisine sahip kullanıcı oluşturur.
```bash
./vendor/bin/sail artisan app:create-admin-user "Sercan Kara" "kara-sercan@hotmail.com" "123456"
# Çıktı: Admin user Sercan Kara created successfully.
```

### 2. İşlem Simülasyonu
Rastgele kullanıcılar arasında detaylı takipli işlemler simüle eder ve veritabanına kaydeder.
```bash
./vendor/bin/sail artisan app:simulate-transactions --count=3
```
**Örnek Çıktı:**
```text
🚀 Starting simulation of 3 transactions with detailed tracing...

# Iteration 1/3
  ├─ Sender: Sercan Kara (kara-sercan@hotmail.com)
  ├─ Source Wallet: TRY (Balance: 1007.24, ID: 019bc6ee...)
  ├─ Receiver: Hulda Rogahn (sheila.smitham@example.com)
  ├─ Attempting Transfer: 53.18 TRY
  └─ SUCCESS: Transaction Created. ID: 019bc6f3... Fee: 2.0000

# Iteration 2/3 ...
```

### 3. İstatistik Önbelleği Yenileme
Dashboard verileri için önbelleği (cache) temizler ve yeniden hesaplar.
```bash
./vendor/bin/sail artisan app:refresh-stats-cache
# Çıktı: 
# Refreshing statistics cache...
# Cache refreshed successfully.
# Cached Users: 7, Daily Volume: 1250.50
```

### 4. Günlük Mutabakat Raporu
Günün tüm işlemlerini özetleyen bir tablo raporu oluşturur.
```bash
./vendor/bin/sail artisan app:daily-reconciliation
```
**Örnek Çıktı:**
```text
+----------+----------+--------------+-----------+-------+
| Type     | Currency | Total Amount | Total Fee | Count |
+----------+----------+--------------+-----------+-------+
| transfer | TRY      | 1,250.00     | 14.50     | 12    |
| deposit  | USD      | 500.00       | 0.00      | 1     |
+----------+----------+--------------+-----------+-------+
```

### 5. Şüpheli/Bekleyen İşlem Kontrolü
24 saatten uzun süredir onay bekleyen (PENDING_REVIEW) şüpheli işlemleri listeler.
```bash
./vendor/bin/sail artisan app:check-pending-transactions
```
**Örnek Çıktı:**
```text
Checking for long-pending transactions...
Found 2 transactions pending review for >24 hours!
+------------+---------------------+----------+--------+----------+---------------------+
| ID         | User                | Type     | Amount | Currency | Created At          |
+------------+---------------------+----------+--------+----------+---------------------+
| 019bc6f3...| cyril99@example.org | transfer | 500.00 | TRY      | 2026-01-14 10:00:00 |
| 019bc6f4...| josh.boyer@test.com | transfer | 750.00 | TRY      | 2026-01-14 11:30:00 |
+------------+---------------------+----------+--------+----------+---------------------+
```

---

## 🧪 Test ve Kalite Güvencesi

Proje, mimariyi ve iş mantığını kapsayan 50'ye yakın test senaryosu içerir.

```bash
# Tüm testleri çalıştır
./vendor/bin/sail artisan test

# Coverage raporu oluştur (storage/coverage altında html çıktı üretir)
./vendor/bin/sail artisan test --coverage-html=storage/coverage
```

Test kapsamı: **Feature Tests (Controllers, APIs), Unit Tests (Logic, Models, Service Pipes).**

---

## 🌍 Localization (Dil Desteği)

API, `Accept-Language` header'ını kullanarak dinamik dil değişimi yapar.
- `tr`: Türkçe hata mesajları ve yanıtlar.
- `en`: İngilizce hata mesajları ve yanıtlar.

Varsayılan dil: `en`

---

## 📮 Postman ve Dökümantasyon

- **Interactive Swagger:** `http://localhost/api/documentation` adresinden Swagger UI'a erişebilirsiniz.
- **Postman Collection:** Kök dizinde bulunan `Derslig_Digital_Wallet_API.postman_collection.json` dosyasını import ederek tüm API'yı test edebilirsiniz.

---

## 🔑 Güvenlik (RBAC)

- **Admin Role:** Tüm kullanıcıları, işlemleri ve şüpheli aktiviteleri görebilir, rapor alabilir ve şüpheli durumları çözebilir.
- **User Role:** Sadece kendi cüzdanlarını görebilir ve kendi adıyla işlem yapabilir.

---

## 📄 Lisans

Bu proje MIT lisansı ile lisanslanmıştır.
