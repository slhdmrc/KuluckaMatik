<?php
// Güvenlik kontrolü
if (!defined('ABSPATH')) exit;

// Giriş kontrolü (Bu kontrol artık shortcode fonksiyonunda yapılıyor)
if (!is_user_logged_in()) {
    echo '<div class="kuluckamatik-container turuncu-giris">';
    echo '<p>Lütfen bu özelliği kullanmak için <a href="' . wp_login_url(get_permalink()) . '">giriş yapın</a> veya <a href="' . wp_registration_url() . '">üye olun</a>.</p>';
    echo '</div>';
    return;
}

// $kulucka_data ve $kayitlar değişkenleri shortcode fonksiyonundan geliyor.
// Form gönderim işlemi artık AJAX ile veya sayfa yenilemesiyle ana PHP dosyasında ele alınabilir.
// Bu şablonda doğrudan POST işlemi yapmayacağız, sadece formu göstereceğiz.

// Eğer form gönderimi sonrası mesaj gösterilecekse (örneğin sayfa yenilemesi ile):
if (isset($_GET['kayit_durum']) && $_GET['kayit_durum'] === 'basarili') {
    echo '<div class="kuluckamatik-container basari-mesaj">';
    echo "<p>Kayıt başarıyla eklendi. Kayıtlar listesinden takip edebilirsiniz.</p>";
    echo '</div>';
} elseif (isset($_GET['kayit_durum']) && $_GET['kayit_durum'] === 'hata') {
     echo '<div class="kuluckamatik-container hata-mesaj">';
     echo "<p>Kayıt eklenirken bir hata oluştu.</p>";
     echo '</div>';
}

?>

<!-- FORM GÖRÜNÜMÜ - GİZLENEBİLİR -->
<div class="kuluckamatik-container">
    <div class="kuluckamatik-header-row">
        <span class="kuluckamatik-header-title">Kuluçka Takip Sistemi</span><br>
        <button id="olustur-buton" class="kuluckamatik-header-btn turuncu-buton">KAYIT OLUŞTUR</button>
    </div>

    <!-- Mesaj Alanı (AJAX için) -->
    <div id="km-mesaj" style="display: none; margin-bottom: 15px;"></div>


    <div id="kuluckamatik-form-container" style="display: none; margin-top: 15px;">
        <!-- Form gönderimi ana PHP dosyasına veya AJAX'a yönlendirilecek -->
        <form id="kuluckamatik-form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="km_yeni_kayit"> <!-- admin-post.php için action -->
            <?php wp_nonce_field('km_yeni_kayit_nonce', 'km_nonce'); ?> <!-- Nonce alanı -->

            <label for="hayvan_turu">Hayvan Türü Seçin:</label>
            <select name="hayvan_turu" id="hayvan_turu" required>
                <option value="">-- Seçin --</option>
                <?php foreach ($kulucka_data as $tur_adi => $veriler): ?>
                    <option value="<?php echo esc_attr($tur_adi); ?>"><?php echo esc_html($tur_adi); ?> (<?php echo esc_html($veriler['sure']); ?> gün)</option>
                <?php endforeach; ?>
            </select>

            <label for="kulucka_baslangic">Kuluçka Başlangıç Tarihi:</label>
            <input type="date" name="kulucka_baslangic" id="kulucka_baslangic" required>
            
            <label for="yumurta_sayisi">Yüklenen Yumurta:</label>
            <input type="number" name="yumurta_sayisi" id="yumurta_sayisi" min="1" value="" required>


            <button type="submit" class="turuncu-buton">Kaydet</button>
        </form>
    </div>
</div>

<!-- KAYITLARI LİSTELE -->
<div class="kuluckamatik-container">
    <h3>Mevcut Kuluçka Kayıtları</h3>
    <div id="kulucka-kayitlar" class="kayit-kartlari-container">
        <?php
        // $kayitlar değişkeni shortcode fonksiyonundan geliyor.
        if (!empty($kayitlar) && is_array($kayitlar)) {
            // Kayıtları başlangıç tarihine göre yeniden eskiye sırala
            usort($kayitlar, function($a, $b) {
                return strtotime($b['baslangic']) - strtotime($a['baslangic']);
            });

            foreach ($kayitlar as $kayit) {
                // Kayıt verilerini al
                $tur_adi = $kayit['tur'] ?? 'Bilinmiyor';
                $baslangic = $kayit['baslangic'] ?? date('Y-m-d');
                $yumurta_sayisi = $kayit['yumurta_sayisi'] ?? 0;
                $kayit_id = $kayit['id'] ?? uniqid();
                $not = $kayit['not'] ?? ''; // Notu al

                // Hayvan verilerini al
                $tur_data = $kulucka_data[$tur_adi] ?? null;
                $sure = $tur_data ? $tur_data['sure'] : 0;
                $sicaklik = $tur_data ? $tur_data['sicaklik'] : '-';
                $nem = $tur_data ? $tur_data['nem'] : '-';
                $son_nem = $tur_data ? $tur_data['son_nem'] : '-';

                // Tarih hesaplamaları
                $baslangic_ts = strtotime($baslangic);
                $cikis_ts = $sure > 0 ? strtotime("+$sure days", $baslangic_ts) : $baslangic_ts;
                $simdi_ts = time();

                $baslangic_formati = date('d.m.Y', $baslangic_ts);
                $cikis_formati = date('d.m.Y', $cikis_ts);

                // Geçen gün, toplam gün, kalan gün
                $gecen_gun = max(0, floor(($simdi_ts - $baslangic_ts) / (60 * 60 * 24)));
                $toplam_gun = $sure;
                $kalan_gun = max(0, $toplam_gun - $gecen_gun);
                if ($simdi_ts >= $cikis_ts) {
                    $gecen_gun = $toplam_gun; // Tamamlandıysa geçen günü toplam güne eşitle
                    $kalan_gun = 0;
                }

                // İlerleme yüzdesi
                $ilerleme = ($toplam_gun > 0) ? min(100, max(0, ($gecen_gun / $toplam_gun) * 100)) : 0;
                if ($simdi_ts >= $cikis_ts) {
                    $ilerleme = 100;
                }

                // İlerleme çubuğu rengi
                $cubuk_renk_class = ($kalan_gun <= 3 && $kalan_gun > 0) ? 'kirmizi' : (($ilerleme >= 70 && $ilerleme < 100) ? 'turuncu' : 'yesil');
                 if ($ilerleme == 100) $cubuk_renk_class = 'mavi'; // Tamamlandıysa mavi

                // Detayları al (yeni yapı)
                $detaylar = $kayit['detaylar'] ?? ['dolsuz_yumurta' => 0, 'cikan_civciv' => 0, 'gelismemis_yumurta' => 0, 'notlar' => ''];
                 // Eski civciv_sayisi varsa ve yeni detaylarda yoksa, onu kullan
                 if (empty($detaylar['cikan_civciv']) && isset($kayit['civciv_sayisi'])) {
                     $detaylar['cikan_civciv'] = $kayit['civciv_sayisi'];
                 }
                 // Ana not alanını detaylardaki not ile birleştir/güncelle
                 $detaylar['notlar'] = !empty($detaylar['notlar']) ? $detaylar['notlar'] : $not;


                // Kartı oluştur
                echo "<div class='kulucka-kart' id='kayit-{$kayit_id}'>";
                echo "<div class='kart-baslik'>";
                echo "<span>" . esc_html($tur_adi) . "</span>";
                echo "<button type='button' class='kulucka-sil cop-kutusu' data-tur='" . esc_attr($tur_adi) . "' data-kayit-id='" . esc_attr($kayit_id) . "' title='Bu kaydı sil'><i class='cop-simge'></i></button>";
                echo "</div>";
                echo "<div class='kart-icerik'>";
                echo "<p><strong>Başlangıç:</strong> " . esc_html($baslangic_formati) . "</p>";
                echo "<p><strong>Tahmini Çıkım:</strong> " . esc_html($cikis_formati) . "</p>";

                // Çıkım ve dölsüz oranı grafikleri (detaylar kaydedildiyse)
                $detay_var = isset($detaylar['cikan_civciv']) && $detaylar['cikan_civciv'] !== '' && isset($detaylar['dolsuz_yumurta']) && $detaylar['dolsuz_yumurta'] !== '';
                if ($detay_var && ($detaylar['cikan_civciv'] > 0 || $detaylar['dolsuz_yumurta'] > 0)) {
                    $cikan = intval($detaylar['cikan_civciv']);
                    $dolsuz = intval($detaylar['dolsuz_yumurta']);
                    $toplam = intval($yumurta_sayisi);
                    $oran = ($toplam > 0) ? round(($cikan / $toplam) * 100) : 0;
                    $dolsuz_oran = ($toplam > 0) ? round(($dolsuz / $toplam) * 100) : 0;
                    $kalan_oran = 100 - $oran - $dolsuz_oran;
                    // Çıkım oranı barı
                    echo "<div style='display: flex; align-items: center; gap: 10px; margin-bottom: 5px;'>";
                    echo "<span style='font-size:12px;'>Çıkım Oranı:</span>";
                    echo "<div style='flex:1; background:#eee; border-radius:8px; height:16px; overflow:hidden;'>";
                    echo "<div style='width:{$oran}%; background:#4caf50; height:16px; display:inline-block;'></div>";
                    echo "</div>";
                    echo "<span style='font-size:12px; min-width:32px; text-align:right;'>{$oran}%</span>";
                    echo "</div>";
                    // Dölsüz pasta dilimi SVG
                    $dolsuz_deg = ($dolsuz_oran / 100) * 360;
                    $cikan_deg = ($oran / 100) * 360;
                    $kalan_deg = 360 - $dolsuz_deg - $cikan_deg;
                    $r = 16; $cx = 20; $cy = 20;
                    $dolsuz_x = $cx + $r * cos(deg2rad(-90));
                    $dolsuz_y = $cy + $r * sin(deg2rad(-90));
                    $cikan_x = $cx + $r * cos(deg2rad(-90 + $dolsuz_deg));
                    $cikan_y = $cy + $r * sin(deg2rad(-90 + $dolsuz_deg));
                    $kalan_x = $cx + $r * cos(deg2rad(-90 + $dolsuz_deg + $cikan_deg));
                    $kalan_y = $cy + $r * sin(deg2rad(-90 + $dolsuz_deg + $cikan_deg));
                    echo "<div style='display:flex; align-items:center; gap:8px; margin-bottom:8px;'>";
                    echo "<svg width='40' height='40' viewBox='0 0 40 40'>";
                    // Dölsüz dilimi
                    $largeArc = $dolsuz_oran > 50 ? 1 : 0;
                    $dolsuz_end_x = $cx + $r * cos(deg2rad(-90 + $dolsuz_deg));
                    $dolsuz_end_y = $cy + $r * sin(deg2rad(-90 + $dolsuz_deg));
                    echo "<path d='M$cx,$cy L$dolsuz_x,$dolsuz_y A$r,$r 0 $largeArc,1 $dolsuz_end_x,$dolsuz_end_y Z' fill='#e74c3c'/>";
                    // Çıkan civciv dilimi
                    $largeArc2 = $oran > 50 ? 1 : 0;
                    $cikan_end_x = $cx + $r * cos(deg2rad(-90 + $dolsuz_deg + $cikan_deg));
                    $cikan_end_y = $cy + $r * sin(deg2rad(-90 + $dolsuz_deg + $cikan_deg));
                    echo "<path d='M$cx,$cy L$dolsuz_end_x,$dolsuz_end_y A$r,$r 0 $largeArc2,1 $cikan_end_x,$cikan_end_y Z' fill='#4caf50'/>";
                    // Kalan dilim (gri)
                    if ($kalan_oran > 0) {
                        $largeArc3 = $kalan_oran > 50 ? 1 : 0;
                        echo "<path d='M$cx,$cy L$cikan_end_x,$cikan_end_y A$r,$r 0 $largeArc3,1 $dolsuz_x,$dolsuz_y Z' fill='#ccc'/>";
                    }
                    echo "</svg>";
                    echo "<span style='font-size:11px; color:#e74c3c;'>Dölsüz: {$dolsuz_oran}%</span>";
                    echo "<span style='font-size:11px; color:#4caf50;'>Çıkan: {$oran}%</span>";
                    echo "</div>";
                }
                echo "<p><strong>Kalan Gün:</strong> " . esc_html($kalan_gun) . "</p>"; // Kalan gün eklendi

                 // Isı ve Nem Bilgisi
                 echo "<div class='isi-nem-bilgisi'>";
                 if ($tur_data) {
                     // Kalan gün 3'ten az ise son nem oranını göster
                     $gosterilecek_nem = ($kalan_gun <= 3 && $kalan_gun > 0) ? $son_nem : $nem;
                     echo "<span class='isi-bilgisi' title='Sıcaklık'>🌡️ " . esc_html($sicaklik) . "°C</span>";
                     echo "<span class='nem-bilgisi' title='Nem'>💧 " . esc_html($gosterilecek_nem) . "%</span>";
                 }
                 echo "</div>";

                echo "<p><strong>Yüklenen Yumurta:</strong> " . esc_html($yumurta_sayisi) . "</p>";

                // İlerleme Çubuğu
                echo "<div class='ilerleme-cubugu-container'>";
                echo "<div class='ilerleme-cubugu'>";
                echo "<div class='ilerleme-dolgu " . esc_attr($cubuk_renk_class) . "' style='width: " . esc_attr($ilerleme) . "%;'></div>";
                echo "</div>";
                echo "<div class='ilerleme-yuzde'>" . round($ilerleme) . "%</div>";
                echo "</div>";
                echo "<p class='gecen-sure-bilgisi'>Geçen Süre: " . esc_html($gecen_gun) . " / " . esc_html($toplam_gun) . " gün</p>"; // Geçen süre bilgisi

                // --- Eski Civciv Formu Kaldırıldı ---

                // Detaylar bölümü her zaman açık
                echo '<div id="detaylar-' . esc_attr($kayit_id) . '" class="detaylar-bolumu">';
                echo '<h4>Kuluçka Sonuçları ve Notlar</h4>';
                echo '<p><label>Dölsüz Yumurta:</label> <input type="number" name="dolsuz_yumurta" value="' . (isset($detaylar['dolsuz_yumurta']) && $detaylar['dolsuz_yumurta'] !== 0 ? esc_attr($detaylar['dolsuz_yumurta']) : '') . '" min="0" max="' . esc_attr($yumurta_sayisi) . '"></p>';
                echo '<p><label>Çıkan Civciv:</label> <input type="number" name="cikan_civciv" value="' . (isset($detaylar['cikan_civciv']) && $detaylar['cikan_civciv'] !== 0 ? esc_attr($detaylar['cikan_civciv']) : '') . '" min="0" max="' . esc_attr($yumurta_sayisi) . '"></p>';
                echo '<p><label>Notlar:</label> <textarea name="notlar" rows="1" style="resize: none; overflow: hidden; min-height: 28px; max-height: 80px;">' . esc_textarea($detaylar['notlar']) . '</textarea></p>';
                echo '<button type="button" class="kaydet-detay-buton turuncu-buton" data-kayit-id="' . esc_attr($kayit_id) . '">Detayları Kaydet</button>';
                echo '<div class="detay-mesaj" style="display: none; margin-top: 10px;"></div>'; // Detay kaydetme mesajı için
                echo '</div>'; // detaylar-bolumu sonu

                echo "</div>"; // kart-icerik sonu
                echo "</div>"; // kulucka-kart sonu
            }
        } else {
            echo '<p id="bos-kayit-mesaji">Hiç kayıt bulunamadı.</p>';
        }
        ?>
    </div>
    <!-- Genel AJAX mesaj alanı (opsiyonel, yukarı taşındı) -->
    <!-- <div id="km-mesaj" style="margin-top:15px; display:none;"></div> -->
</div>

<?php // ob_end_flush(); // Bu satır genellikle shortcode içinde gerekmez, WP halleder. ?>
