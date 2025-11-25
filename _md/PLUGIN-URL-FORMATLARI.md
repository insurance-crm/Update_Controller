# Plugin Kaynak URL Formatları / Plugin Source URL Formats

## Desteklenen URL Türleri / Supported URL Types

### ✅ Doğrudan ZIP İndirme / Direct ZIP Download

**Türkçe:**
En güvenilir yöntem, doğrudan ZIP dosyası indirme bağlantısı kullanmaktır.

**English:**
The most reliable method is to use a direct ZIP file download link.

**Örnek / Example:**
```
https://example.com/plugins/my-plugin.zip
https://yoursite.com/downloads/plugin-v2.9.1.zip
```

---

### ✅ GitHub Repository

**Türkçe:**
GitHub repository URL'leri otomatik olarak dönüştürülür.

**English:**
GitHub repository URLs are automatically converted.

**Desteklenen Formatlar / Supported Formats:**
```
✅ https://github.com/username/repository
✅ https://github.com/username/repository/archive/refs/heads/main.zip
✅ https://github.com/username/repository/releases/download/v1.0.0/plugin.zip
```

---

### ✅ Google Drive

**Türkçe:**
Google Drive paylaşım linkleri otomatik olarak doğrudan indirme linklerine dönüştürülür.

**English:**
Google Drive sharing links are automatically converted to direct download links.

**Doğru Format / Correct Format:**
```
✅ https://drive.google.com/file/d/FILE_ID/view?usp=sharing
✅ https://drive.google.com/uc?export=download&id=FILE_ID
```

**Yanlış Format / Wrong Format:**
```
❌ https://drive.google.com/file/d/FILE_ID/edit
❌ https://drive.google.com/open?id=FILE_ID
```

**Google Drive Linki Nasıl Alınır / How to Get Google Drive Link:**

**Türkçe:**
1. Google Drive'da dosyaya sağ tıklayın
2. "Paylaş" (Share) seçeneğine tıklayın
3. "Bağlantıyı olan herkes görüntüleyebilir" (Anyone with the link can view) seçin
4. "Bağlantıyı kopyala" (Copy link) butonuna tıklayın
5. Kopyalanan linki Update Controller'a yapıştırın

**English:**
1. Right-click on the file in Google Drive
2. Click "Share"
3. Select "Anyone with the link can view"
4. Click "Copy link"
5. Paste the copied link into Update Controller

---

## ❌ Yaygın Hatalar / Common Errors

### "Incompatible Archive" Hatası

**Türkçe:**
Bu hata, indirilen dosyanın ZIP formatında olmadığı anlamına gelir.

**English:**
This error means the downloaded file is not in ZIP format.

**Yaygın Nedenler / Common Causes:**

1. **HTML Sayfası İndiriliyor / Downloading HTML Page**
   - Google Drive sharing linki yanlış formatta
   - Dropbox, OneDrive gibi servislerde "preview" linki kullanılıyor
   - Dosya korumalı veya giriş gerektiriyor

2. **Dosya Formatı Yanlış / Wrong File Format**
   - Kaynak dosya .zip değil (.rar, .7z, .tar.gz vb.)
   - Dosya bozuk veya yarım inmiş

**Çözüm / Solution:**

1. **URL'yi Kontrol Edin / Check URL:**
   ```
   ❌ Yanlış: https://drive.google.com/file/d/ABC123/view?usp=sharing (eski format)
   ✅ Doğru: Plugin otomatik dönüştürür
   ```

2. **Dosya Formatını Kontrol Edin / Check File Format:**
   - Dosya mutlaka .zip formatında olmalı
   - Dosya boyutu 0 KB'dan büyük olmalı
   - Dosya erişilebilir olmalı (giriş gerektirmemeli)

3. **Manuel Test / Manual Test:**
   - URL'yi tarayıcıya yapıştırın
   - Dosya otomatik inmeye başlamalı (HTML sayfası açılmamalı)
   - İndirilen dosyanın .zip olduğunu doğrulayın

---

## Sorun Giderme / Troubleshooting

### Debug Loglarını Kontrol Edin / Check Debug Logs

**Step 0 FAILED** görüyorsanız:

**Türkçe:**
```
[Update Controller: Step 0 - Downloading plugin from [URL]]
[Update Controller: Downloaded file mime type: text/html]  ← SORUN: HTML indiriliyor!
```

Bu, URL'in doğrudan indirme linki değil, bir web sayfası olduğunu gösterir.

**English:**
```
[Update Controller: Step 0 - Downloading plugin from [URL]]
[Update Controller: Downloaded file mime type: text/html]  ← PROBLEM: Downloading HTML!
```

This indicates the URL is a web page, not a direct download link.

---

### Google Drive için Özel Notlar / Special Notes for Google Drive

**Türkçe:**

1. **Dosya Boyutu Limiti:**
   - Google Drive, büyük dosyalar için (>25MB) virüs taraması uyarısı gösterir
   - Bu, doğrudan indirmeyi engelleyebilir
   - Çözüm: Dosyayı daha küçük parçalara bölün veya başka bir hosting kullanın

2. **Paylaşım İzinleri:**
   - Dosya "Bağlantıyı olan herkes" için paylaşılmış olmalı
   - Giriş gerektirmemeli
   - "Sadece görüntüle" yetkisi yeterli

3. **Alternatif Yöntem:**
   - Google Drive yerine GitHub Releases kullanın
   - Veya kendi sunucunuzda barındırın

**English:**

1. **File Size Limit:**
   - Google Drive shows virus scan warning for large files (>25MB)
   - This can prevent direct downloads
   - Solution: Split into smaller files or use different hosting

2. **Sharing Permissions:**
   - File must be shared with "Anyone with the link"
   - Must not require login
   - "View only" permission is sufficient

3. **Alternative Method:**
   - Use GitHub Releases instead of Google Drive
   - Or host on your own server

---

## Önerilen Çözümler / Recommended Solutions

### 1. GitHub Releases (En İyi / Best)

**Türkçe:**
1. GitHub repository'nize gidin
2. "Releases" sekmesine tıklayın
3. "Create a new release" butonuna tıklayın
4. ZIP dosyanızı "Assets" olarak yükleyin
5. Release'i yayınlayın
6. ZIP dosyasının URL'sini kopyalayın ve Update Controller'a ekleyin

**English:**
1. Go to your GitHub repository
2. Click "Releases" tab
3. Click "Create a new release"
4. Upload your ZIP file as an "Asset"
5. Publish the release
6. Copy the ZIP file URL and add to Update Controller

### 2. Kendi Sunucunuz / Your Own Server

**Türkçe:**
1. ZIP dosyasını kendi web sunucunuza yükleyin
2. Doğrudan erişilebilir URL oluşturun
3. URL'yi Update Controller'a ekleyin

**English:**
1. Upload ZIP file to your web server
2. Create directly accessible URL
3. Add URL to Update Controller

**Örnek / Example:**
```
https://yoursite.com/downloads/my-plugin-v2.9.1.zip
```

### 3. WordPress Plugin Repository

**Türkçe:**
WordPress.org plugin repository'sinde ise, SVN URL'sini kullanın.

**English:**
If on WordPress.org plugin repository, use SVN URL.

---

## Hızlı Kontrol Listesi / Quick Checklist

**Eklenti güncellemesi yapmadan önce / Before updating plugin:**

- [ ] URL doğrudan bir .zip dosyasına mı işaret ediyor?
- [ ] URL tarayıcıda açıldığında dosya otomatik indiriliyor mu?
- [ ] Dosya erişime açık mı? (Giriş gerektirmiyor mu?)
- [ ] Dosya boyutu 0'dan büyük mü?
- [ ] Google Drive kullanıyorsanız, paylaşım linki doğru mu?
- [ ] GitHub kullanıyorsanız, repository URL'si geçerli mi?

**Before updating plugin:**

- [ ] Does URL point directly to a .zip file?
- [ ] Does the file download automatically when URL is opened in browser?
- [ ] Is the file publicly accessible? (No login required?)
- [ ] Is file size greater than 0?
- [ ] If using Google Drive, is the sharing link correct?
- [ ] If using GitHub, is the repository URL valid?

---

## Test Etme / Testing

**Türkçe:**

URL'nizi test etmek için:
1. URL'yi yeni bir tarayıcı sekmesinde açın
2. Dosya otomatik inmeye başlamalı
3. İndirilen dosya .zip uzantılı olmalı
4. ZIP dosyasını açıp içeriğini kontrol edin

**English:**

To test your URL:
1. Open URL in a new browser tab
2. File should start downloading automatically
3. Downloaded file should have .zip extension
4. Open the ZIP file and check contents

---

## Yardım / Help

**Türkçe:**
Hala sorun yaşıyorsanız, debug loglarını kontrol edin:
```
wp-content/debug.log
```

Şu satırları arayın:
```
Update Controller: Downloading from URL: [your-url]
Update Controller: Downloaded file mime type: [type]
```

**English:**
If still having issues, check debug logs:
```
wp-content/debug.log
```

Look for these lines:
```
Update Controller: Downloading from URL: [your-url]
Update Controller: Downloaded file mime type: [type]
```

Eğer `mime type: text/html` görüyorsanız, URL yanlış formatta.

If you see `mime type: text/html`, the URL is in wrong format.
