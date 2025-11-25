# "Forbidden" Hatasının Çözümü / Solution for "Forbidden" Error

## Sorun / Problem
Update işlemi sırasında "Forbidden" hatası alabilirsiniz. Bu hata iki farklı aşamada oluşabilir:

1. **Step 0 (Download)**: Plugin dosyası kaynak URL'den indirilirken (örn: balkay.net veya GitHub)
2. **Step 1+ (Authentication/Upload)**: Uzak WordPress sitesine erişim sırasında

If you get a "Forbidden" error during update, it can occur at two different stages:

1. **Step 0 (Download)**: When downloading the plugin file from source URL (e.g., balkay.net or GitHub)
2. **Step 1+ (Authentication/Upload)**: When accessing the remote WordPress site

---

## Step 0'da Forbidden Hatası / Forbidden Error at Step 0

### Sorun / Problem
```
Update Controller: Step 0 FAILED - Download error: Forbidden
```

Bu hata, kaynak sunucunun dosya indirme isteğini reddettiği anlamına gelir.
This error means the source server rejected the download request.

### Neden Oluyor? / Why Does This Happen?

**Türkçe:**
403 Forbidden hatası genellikle şu sebeplerden oluşur:

1. **Hotlink Koruması**: Sunucu, farklı domain'lerden gelen istekleri engelliyor
2. **User-Agent Filtreleme**: Sunucu, tarayıcı olmayan istekleri engelliyor  
3. **Güvenlik Eklentileri**: Wordfence, Sucuri gibi eklentiler otomatik indirmeleri engelliyor
4. **mod_security Kuralları**: Sunucu seviyesinde güvenlik kuralları indirmeyi engelliyor

**English:**
403 Forbidden error usually occurs due to:

1. **Hotlink Protection**: Server blocks requests from different domains
2. **User-Agent Filtering**: Server blocks non-browser requests
3. **Security Plugins**: Plugins like Wordfence, Sucuri block automated downloads
4. **mod_security Rules**: Server-level security rules block downloads

### Çözüm (v1.0.2+) / Solution (v1.0.2+)

**Türkçe:** Plugin artık gerçek bir tarayıcı gibi davranarak istek gönderiyor:
- Chrome tarayıcı User-Agent header'ı kullanıyor
- Kaynak domain'i Referer olarak gönderiyor (hotlink korumasını aşmak için)
- Tarayıcı güvenlik header'ları ekliyor (Sec-Fetch-*)

**English:** The plugin now mimics a real browser request:
- Uses Chrome browser User-Agent header
- Sends source domain as Referer (to bypass hotlink protection)
- Adds browser security headers (Sec-Fetch-*)

### Sunucu Tarafı Kontroller / Server-Side Checks
Eğer hala Forbidden hatası alıyorsanız, kaynak sunucuda (balkay.net) şunları kontrol edin:

1. **Dosyanın URL'sini kontrol edin**: Tarayıcıda URL'yi açarak dosyanın erişilebilir olduğunu doğrulayın
2. **Hotlink korumasını devre dışı bırakın**: cPanel veya .htaccess'te hotlink koruması varsa ZIP dosyaları için izin verin
3. **IP engellemesini kontrol edin**: Sunucu IP adresinizi engellemiş olabilir
4. **.htaccess kurallarını kontrol edin**: Sunucudaki .htaccess dosyası ZIP dosyalarını engelliyor olabilir
5. **Güvenlik eklentisini kontrol edin**: Wordfence veya Sucuri gibi eklentiler indirmeyi engelliyor olabilir

**Kaynak sunucudaki .htaccess dosyasına eklenebilecek izin kuralı:**
```apache
# Allow ZIP file downloads
<FilesMatch "\.zip$">
    Order allow,deny
    Allow from all
    Require all granted
</FilesMatch>
```

If you still get Forbidden error, check on the source server (balkay.net):

1. **Check the file URL**: Verify the file is accessible by opening the URL in a browser
2. **Disable hotlink protection**: If hotlink protection is enabled in cPanel or .htaccess, allow ZIP files
3. **Check IP blocking**: The server may have blocked your IP address
4. **Check .htaccess rules**: The .htaccess file on the server may be blocking ZIP files
5. **Check security plugins**: Wordfence or Sucuri plugins may be blocking downloads

**Rule to add to .htaccess on source server:**
```apache
# Allow ZIP file downloads
<FilesMatch "\.zip$">
    Order allow,deny
    Allow from all
    Require all granted
</FilesMatch>
```

---

## Adım Adım Çözüm / Step-by-Step Solution

### 1. Companion Plugin'i Kontrol Edin / Check Companion Plugin

**Türkçe:**
1. Uzak WordPress sitesine giriş yapın (güncellenmesini istediğiniz site)
2. **Eklentiler** menüsüne gidin
3. "Update Controller Companion" eklentisinin **yüklü ve aktif** olduğunu kontrol edin
4. Eğer yüklü değilse:
   - `companion-plugin/update-controller-companion.php` dosyasını kopyalayın
   - Uzak sitede `/wp-content/plugins/update-controller-companion/` dizinine yükleyin
   - Eklentiyi etkinleştirin

**English:**
1. Log into the remote WordPress site (the site you want to update)
2. Go to **Plugins** menu
3. Check that "Update Controller Companion" plugin is **installed and active**
4. If not installed:
   - Copy the `companion-plugin/update-controller-companion.php` file
   - Upload to `/wp-content/plugins/update-controller-companion/` on the remote site
   - Activate the plugin

---

### 2. Application Password Oluşturun / Create Application Password

**Türkçe:**
1. Uzak WordPress sitesinde **Kullanıcılar > Profil** bölümüne gidin
2. Aşağı kaydırıp **Application Passwords** bölümünü bulun
3. Yeni bir uygulama şifresi oluşturun (isim: "Update Controller")
4. Oluşturulan şifreyi kopyalayın (BOŞLUKLARLA BİRLİKTE)
5. Update Controller sitesinde:
   - **Update Controller > Sites** menüsüne gidin
   - Site'yi düzenleyin (Edit)
   - Kopyaladığınız Application Password'ü yapıştırın
   - Kaydedin (Save)

**English:**
1. On the remote WordPress site, go to **Users > Profile**
2. Scroll down to find **Application Passwords** section
3. Create a new application password (name: "Update Controller")
4. Copy the generated password (WITH SPACES)
5. On Update Controller site:
   - Go to **Update Controller > Sites**
   - Edit the site
   - Paste the Application Password
   - Save

**UYARI / WARNING:** Normal WordPress şifrenizi KULLANMAYIN! Application Password kullanmalısınız.
Do NOT use your regular WordPress password! Use Application Password.

---

### 3. Bağlantıyı Test Edin / Test Connection

**Türkçe:**
1. **Update Controller > Sites** menüsüne gidin
2. Site satırındaki **Test** düğmesine tıklayın
3. Sonucu kontrol edin:
   - ✅ **Başarılı**: "Connection successful! Companion plugin is active..."
   - ❌ **Başarısız**: Hata mesajını okuyun

**English:**
1. Go to **Update Controller > Sites**
2. Click the **Test** button on the site row
3. Check the result:
   - ✅ **Success**: "Connection successful! Companion plugin is active..."
   - ❌ **Failed**: Read the error message

---

### 4. Debug Log'ları Kontrol Edin / Check Debug Logs

**Türkçe:**

Yeni güncellenmiş kodla birlikte, debug log'ları artık hangi adımda hata oluştuğunu gösteriyor:

1. `wp-config.php` dosyasında debug modu etkin olmalı:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

2. Update işlemini tekrar deneyin

3. `wp-content/debug.log` dosyasını açın ve şu satırları arayın:
```
Update Controller: Step 0 - Downloading plugin from [URL]
Update Controller: Step 0 SUCCESS - Plugin downloaded to [path]
Update Controller: Step 1 - Starting authentication to [site URL]
Update Controller: Step 1 SUCCESS - Authentication successful
Update Controller: Step 2 - Starting file upload
Update Controller: Step 2 SUCCESS - File uploaded
Update Controller: Step 3 - Deactivating plugin
Update Controller: Step 4 - Installing plugin
Update Controller: Step 4 SUCCESS - Plugin installed
Update Controller: Step 5 - Reactivating plugin
Update Controller: All steps completed successfully
```

4. Hangi adımda "FAILED" görüyorsanız, o adımla ilgili çözüme bakın:

**Step 0 FAILED (Download):**
- Update source URL'i doğru mu?
- GitHub URL'si doğru formatta mı?
- İnternet bağlantınız var mı?

**Step 1 FAILED (Authentication):**
- Application Password doğru mu?
- Kullanıcı adı doğru mu?
- Uzak sitede REST API etkin mi?

**Step 2 FAILED (Upload):**
- Companion plugin aktif mi?
- Kullanıcının upload_files yetkisi var mı?
- Dosya boyutu limiti aşıldı mı?

**Step 4 FAILED (Installation):**
- Companion plugin güncel mi?
- Hedef dizin yazılabilir mi?
- PHP execution time limit yeterli mi?

**English:**

With the newly updated code, debug logs now show which step failed:

1. Debug mode must be enabled in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

2. Try the update again

3. Open `wp-content/debug.log` and look for these lines:
```
Update Controller: Step 0 - Downloading plugin from [URL]
Update Controller: Step 0 SUCCESS - Plugin downloaded to [path]
Update Controller: Step 1 - Starting authentication to [site URL]
Update Controller: Step 1 SUCCESS - Authentication successful
Update Controller: Step 2 - Starting file upload
Update Controller: Step 2 SUCCESS - File uploaded
Update Controller: Step 3 - Deactivating plugin
Update Controller: Step 4 - Installing plugin
Update Controller: Step 4 SUCCESS - Plugin installed
Update Controller: Step 5 - Reactivating plugin
Update Controller: All steps completed successfully
```

4. If you see "FAILED" at any step, see the solution for that step:

**Step 0 FAILED (Download):**
- Is the update source URL correct?
- Is the GitHub URL in correct format?
- Do you have internet connection?

**Step 1 FAILED (Authentication):**
- Is the Application Password correct?
- Is the username correct?
- Is REST API enabled on remote site?

**Step 2 FAILED (Upload):**
- Is companion plugin active?
- Does user have upload_files capability?
- Is file size limit exceeded?

**Step 4 FAILED (Installation):**
- Is companion plugin up to date?
- Is target directory writable?
- Is PHP execution time limit sufficient?

---

## Yaygın Hatalar ve Çözümleri / Common Errors and Solutions

### "Access forbidden (403)"
**Neden / Cause:** Application Passwords etkin değil veya yanlış şifre
**Çözüm / Solution:** Yukarıdaki Adım 2'yi takip edin

### "Companion plugin test failed (HTTP 404)"
**Neden / Cause:** Companion plugin yüklü değil
**Çözüm / Solution:** Yukarıdaki Adım 1'i takip edin

### "Invalid credentials (401)"
**Neden / Cause:** Kullanıcı adı veya Application Password yanlış
**Çözüm / Solution:** Kimlik bilgilerini kontrol edin, Application Password'ü yeniden oluşturun

### "Upload failed: Failed to upload plugin file: rest_forbidden"
**Neden / Cause:** Kullanıcının dosya yükleme yetkisi yok
**Çözüm / Solution:** 
- Uzak sitede kullanıcının "Administrator" rolü olmalı
- Veya "upload_files" capability'si olmalı

---

## Hızlı Kontrol Listesi / Quick Checklist

- [ ] Uzak sitede Companion plugin yüklü ve aktif mi?
- [ ] Application Password oluşturuldu mu ve doğru kopyalandı mı?
- [ ] Site URL'i doğru mu? (https:// ile başlıyor mu?)
- [ ] Test butonu başarılı sonuç veriyor mu?
- [ ] Debug log'ları hangi adımda hata olduğunu gösteriyor?
- [ ] Uzak sitede kullanıcı Administrator rolünde mi?
- [ ] WordPress 5.6+ sürümü kullanılıyor mu?

---

## Hala Çalışmıyor mu? / Still Not Working?

**Türkçe:**

1. **Debug log'ları paylaşın**: `wp-content/debug.log` dosyasındaki update ile ilgili tüm satırları kopyalayın
2. **Test sonucunu paylaşın**: Test butonuna tıkladığınızda çıkan mesajın ekran görüntüsünü alın
3. **Site bilgilerini kontrol edin**: 
   - Uzak site WordPress versiyonu: ?
   - Companion plugin versiyonu: ?
   - PHP versiyonu: ?

**English:**

1. **Share debug logs**: Copy all update-related lines from `wp-content/debug.log`
2. **Share test result**: Take a screenshot of the message when clicking Test button
3. **Check site info**:
   - Remote site WordPress version: ?
   - Companion plugin version: ?
   - PHP version: ?

---

## Önemli Notlar / Important Notes

**Türkçe:**
- Companion plugin MUTLAKA uzak sitede yüklü olmalıdır (güncellenmesini istediğiniz site)
- Application Password normal WordPress şifresinden FARKLIDIR
- Test butonu her şeyin doğru çalıştığını kontrol etmek için kullanılmalıdır
- Debug log'ları artık hangi adımda sorun olduğunu gösteriyor

**English:**
- Companion plugin MUST be installed on the remote site (the site you want to update)
- Application Password is DIFFERENT from regular WordPress password
- Test button should be used to verify everything works correctly
- Debug logs now show which step is failing
