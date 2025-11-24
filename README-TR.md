# Update Controller

WordPress sitelerindeki eklentileri web veya GitHub repository kaynaklarından otomatik olarak güncelleyen WordPress eklentisi.

## Özellikler

- Tek bir kontrol panelinden birden fazla WordPress sitesini yönetme
- Özel kaynaklardan otomatik eklenti güncellemelerini yapılandırma
- Web URL'leri ve GitHub repository'leri için destek
- Şifreli kimlik bilgisi saklama
- Manuel ve otomatik güncelleme zamanlama
- Kullanıcı dostu yönetici arayüzü

## Kurulum

### Ana Eklenti (Kontrol Sitesi)

1. `update-controller` klasörünü `/wp-content/plugins/` dizinine yükleyin
2. Eklentiyi WordPress'teki 'Eklentiler' menüsünden etkinleştirin
3. Yönetici menüsünde 'Update Controller'a gidin

### Yardımcı Eklenti (Hedef Siteler)

**Önemli**: Uzaktan güncellemek istediğiniz her WordPress sitesine kurulmalıdır!

1. `companion-plugin/update-controller-companion.php` dosyasını kopyalayın
2. Her hedef sitede `/wp-content/plugins/update-controller-companion/` dizinine yükleyin
3. Her hedef sitede 'Eklentiler' menüsünden etkinleştirin
4. Kimlik doğrulama için Uygulama Şifrelerini yapılandırın

Detaylı talimatlar için [companion-plugin/README.md](companion-plugin/README.md) dosyasına bakın.

## Kullanım

### WordPress Sitelerini Ekleme

1. **Update Controller > Sites** menüsüne gidin
2. **Add New Site** butonuna tıklayın
3. Aşağıdaki bilgileri doldurun:
   - **Site Name**: Site için kolay hatırlanır bir isim
   - **Site URL**: WordPress sitesinin tam URL'si (örn: https://example.com)
   - **Admin Username**: WordPress yönetici kullanıcı adı
   - **Admin Password**: WordPress yönetici şifresi veya Uygulama Şifresi

**Not**: Daha iyi güvenlik için, ana yönetici şifresi yerine WordPress Uygulama Şifreleri kullanın.

### Eklenti Güncellemelerini Yapılandırma

1. **Update Controller > Plugins** menüsüne gidin
2. **Add Plugin Configuration** butonuna tıklayın
3. Aşağıdaki bilgileri doldurun:
   - **WordPress Site**: Hedef siteyi seçin
   - **Plugin Name**: Eklenti için kolay hatırlanır isim
   - **Plugin Slug**: Eklenti dizini/ana dosya (örn: `akismet/akismet.php`)
   - **Update Source URL**: Doğrudan indirme URL'si veya GitHub repository URL'si
   - **Source Type**: 'Web URL' veya 'GitHub Repository' seçin
   - **Enable Automatic Updates**: Zamanlanmış güncellemeleri etkinleştirmek için işaretleyin

### Güncellemeleri Çalıştırma

**Manuel Güncellemeler:**
- **Update Controller > Plugins** menüsüne gidin
- Herhangi bir eklenti yapılandırmasının yanındaki **Update Now** butonuna tıklayın

**Otomatik Güncellemeler:**
- "Enable Automatic Updates" işaretli eklentiler için varsayılan olarak etkindir
- WordPress cron üzerinden günlük olarak çalışır
- Eklenti kodunu değiştirerek özelleştirilebilir

## Kaynak URL Örnekleri

### Web URL
```
https://example.com/downloads/my-plugin.zip
https://cdn.example.com/plugins/latest/plugin-name.zip
```

### GitHub Repository
```
https://github.com/username/repository
https://github.com/username/repository/archive/refs/heads/main.zip
https://github.com/username/repository/releases/download/v1.0.0/plugin.zip
```

## Güvenlik

- Yönetici kimlik bilgileri AES-256-CBC kullanılarak şifrelenir
- Tüm AJAX istekleri WordPress nonce'ları ile korunur
- Tüm işlemler için `manage_options` yetkisi gerektirir
- Kimlik bilgileri kaydedildikten sonra arayüzde asla görüntülenmez

## Gereksinimler

- WordPress 5.0 veya üzeri
- PHP 7.2 veya üzeri
- Şifreleme için OpenSSL PHP uzantısı

## Teknik Detaylar

### Veritabanı Tabloları

**uc_sites:**
- WordPress site bilgilerini ve kimlik bilgilerini saklar

**uc_plugins:**
- Eklenti güncelleme yapılandırmalarını saklar

### Güncelleme Süreci

1. Eklentiyi belirtilen kaynaktan indir
2. Uzak WordPress sitesi ile kimlik doğrulama yap
3. Eklenti dosyasını uzak siteye yükle
4. Mevcut eklenti sürümünü devre dışı bırak
5. Güncellenmiş eklentiyi yükle
6. Eklentiyi yeniden etkinleştir

## Önemli Notlar

### Uygulama Şifreleri

WordPress 5.6+ için Uygulama Şifrelerini kullanmanız önerilir:

1. Hedef WordPress sitesinde **Kullanıcılar > Profil** bölümüne gidin
2. **Application Passwords** bölümüne inin
3. Yeni bir uygulama şifresi oluşturun
4. Ana yönetici şifresi yerine bu şifreyi Update Controller'da kullanın

### Uzak Site Gereksinimleri

Uzak WordPress sitesi şunlara sahip olmalıdır:
- REST API etkin (WordPress'te varsayılan)
- Uygulama Şifreleri veya temel kimlik doğrulama yoluyla kimlik doğrulamayı kabul etme
- Eklenti kurulumu için uygun dosya izinleri

### Özelleştirme

Güncelleme zamanlamasını özelleştirmek için `update-controller.php` dosyasındaki cron zamanlamasını değiştirin:

```php
// 'daily'yi 'hourly', 'twicedaily' veya özel bir aralık ile değiştirin
wp_schedule_event(time(), 'daily', 'uc_scheduled_update');
```

## Destek

Sorunlar ve özellik istekleri için lütfen şu adresi ziyaret edin:
https://github.com/insurance-crm/Update_Controller

## Lisans

GPL v2 veya sonrası

## Değişiklik Günlüğü

### 1.0.0
- İlk sürüm
- Site yönetimi
- Eklenti güncelleme yapılandırması
- Manuel ve otomatik güncellemeler
- GitHub ve web URL desteği
- Kimlik bilgisi şifreleme
