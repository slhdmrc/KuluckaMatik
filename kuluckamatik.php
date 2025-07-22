<?php
/**
 * Plugin Name: Kuluçkamatik
 * Description: Kuluçka süresi takip sistemi.
 * Version: 1.0
 * Author: Salih
 */

if (!defined('ABSPATH')) exit;

// Hayvan türleri ve kuluçka bilgileri
function get_kulucka_data() {
    return array(
        'Tavuk' => array('sure' => 21, 'sicaklik' => '37,5', 'nem' => '50-55', 'son_nem' => '65-70'),
        'Bıldırcın' => array('sure' => 17, 'sicaklik' => '37,5', 'nem' => '45-55', 'son_nem' => '65-70'),
        'Keklik' => array('sure' => 24, 'sicaklik' => '37,6', 'nem' => '50-55', 'son_nem' => '65-70'),
        'Hindi' => array('sure' => 28, 'sicaklik' => '37,5', 'nem' => '55-60', 'son_nem' => '70-75'),
        'Ördek' => array('sure' => 28, 'sicaklik' => '37,5', 'nem' => '60-65', 'son_nem' => '75-80'),
        'Kaz' => array('sure' => 30, 'sicaklik' => '37.2-37.4', 'nem' => '60-65', 'son_nem' => '80-85'),
        'Sülün' => array('sure' => 24, 'sicaklik' => '37,5', 'nem' => '50-55', 'son_nem' => '65-70'),
        'Güvercin' => array('sure' => 17, 'sicaklik' => '37,5', 'nem' => '50', 'son_nem' => '65'), // Corrected duration
        'Tavuskuşu' => array('sure' => 28, 'sicaklik' => '37,5', 'nem' => '50-55', 'son_nem' => '65-70'),
        'Devekuşu' => array('sure' => 42, 'sicaklik' => '36.0-36.5', 'nem' => '25-35', 'son_nem' => '40-50'),
    );
}

// Stil ve JS çağır
function km_enqueue_scripts() {
    wp_enqueue_style('km-style', plugin_dir_url(__FILE__) . 'css/style.css', array(), '1.0.2');
    wp_enqueue_script('km-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0.2', true);
    
    // AJAX için gerekli değişkenleri gönder
    wp_localize_script('km-script', 'kmAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('kuluckamatik_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'km_enqueue_scripts');

// AJAX silme işlemi için eylem
function km_sil_kayit() {
    global $wpdb;
    // Nonce kontrolü
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kuluckamatik_nonce')) {
        wp_send_json_error('Güvenlik doğrulaması başarısız.');
        exit;
    }
    
    // Kullanıcı kontrolü
    if (!is_user_logged_in()) {
        wp_send_json_error('Oturum açmanız gerekiyor.');
        exit;
    }
    
    // Tür ve ID kontrolü
    if (!isset($_POST['tur']) || empty($_POST['tur']) || !isset($_POST['kayit_id']) || empty($_POST['kayit_id'])) {
        wp_send_json_error('Geçersiz parametreler.');
        exit;
    }
    
    $kullanici_id = get_current_user_id();
    $kayit_id = sanitize_text_field($_POST['kayit_id']);
    
    // Tablo adı
    $table_name = $wpdb->prefix . 'kulucka_kayitlar';
    // Kayıt sil
    $sonuc = $wpdb->delete(
        $table_name,
        array(
            'id' => $kayit_id,
            'user_id' => $kullanici_id
        ),
        array('%s', '%d')
    );
    
    if ($sonuc !== false && $sonuc > 0) {
        wp_send_json_success(array(
            'message' => 'Kayıt başarıyla silindi.',
            'kayit_id' => $kayit_id
        ));
    } else {
        wp_send_json_error('Silme işlemi başarısız oldu veya kayıt bulunamadı.');
    }
    exit;
}
add_action('wp_ajax_km_sil_kayit', 'km_sil_kayit');

// AJAX ile civciv sayısı güncelleme
function km_guncelle_civciv() {
    // Nonce kontrolü
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kuluckamatik_nonce')) {
        wp_send_json_error('Güvenlik doğrulaması başarısız.');
        exit;
    }
    
    // Kullanıcı kontrolü
    if (!is_user_logged_in()) {
        wp_send_json_error('Oturum açmanız gerekiyor.');
        exit;
    }
    
    // Gerekli parametreleri kontrol et
    if (!isset($_POST['kayit_id']) || empty($_POST['kayit_id']) || !isset($_POST['civciv'])) {
        wp_send_json_error('Geçersiz parametreler.');
        exit;
    }
    
    $kullanici_id = get_current_user_id();
    $kayit_id = sanitize_text_field($_POST['kayit_id']);
    $civciv_sayisi = intval($_POST['civciv']);
    
    // Meta verileri al
    $kayitlar = get_user_meta($kullanici_id, 'kuluckamatik_kayitlar', true);
    if (empty($kayitlar)) $kayitlar = array();
    
    // Kaydı bul ve güncelle
    $kayit_guncellendi = false;
    $guncel_kayit = null;
    
    foreach ($kayitlar as $index => $kayit) {
        if ($kayit['id'] === $kayit_id) {
            $kayitlar[$index]['civciv_sayisi'] = $civciv_sayisi;
            $kayit_guncellendi = true;
            $guncel_kayit = $kayitlar[$index];
            break;
        }
    }
    
    // Güncellenmiş diziyi kaydet
    $sonuc = update_user_meta($kullanici_id, 'kuluckamatik_kayitlar', $kayitlar);
    
    // Global veritabanında da güncelle
    if ($guncel_kayit) {
        update_option('kuluckamatik_kayit_' . $kayit_id, $guncel_kayit);
    }
    
    if ($sonuc && $kayit_guncellendi) {
        wp_send_json_success(array(
            'message' => 'Civciv sayısı güncellendi.',
            'kayit_id' => $kayit_id,
            'civciv_sayisi' => $civciv_sayisi
        ));
    } else {
        wp_send_json_error('Güncelleme başarısız oldu veya kayıt bulunamadı.');
    }
    exit;
}
add_action('wp_ajax_km_guncelle_civciv', 'km_guncelle_civciv');


// AJAX ile kuluçka detaylarını kaydetme
function km_kaydet_detaylar() {
    global $wpdb;
    // Nonce kontrolü
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kuluckamatik_nonce')) {
        wp_send_json_error('Güvenlik doğrulaması başarısız.');
        exit;
    }

    // Kullanıcı kontrolü
    if (!is_user_logged_in()) {
        wp_send_json_error('Oturum açmanız gerekiyor.');
        exit;
    }

    // Gerekli parametreleri kontrol et
    if (!isset($_POST['kayit_id']) || empty($_POST['kayit_id'])) {
        wp_send_json_error('Kayıt ID eksik.');
        exit;
    }

    $kullanici_id = get_current_user_id();
    $kayit_id = sanitize_text_field($_POST['kayit_id']);

    // Kaydedilecek detayları al ve temizle
    $detaylar = array(
        'detay_dolsuz_yumurta' => isset($_POST['dolsuz_yumurta']) ? intval($_POST['dolsuz_yumurta']) : 0,
        'detay_cikan_civciv' => isset($_POST['cikan_civciv']) ? intval($_POST['cikan_civciv']) : 0,
        'detay_notlar' => isset($_POST['notlar']) ? sanitize_textarea_field($_POST['notlar']) : '',
    );

    // Tablo adı
    $table_name = $wpdb->prefix . 'kulucka_kayitlar';

    // Kayıt güncelle
    $sonuc = $wpdb->update(
        $table_name,
        $detaylar,
        array(
            'id' => $kayit_id,
            'user_id' => $kullanici_id
        ),
        array(
            '%d', // detay_dolsuz_yumurta
            '%d', // detay_cikan_civciv
            '%s'  // detay_notlar
        ),
        array('%s', '%d')
    );

    if ($sonuc !== false) {
        wp_send_json_success(array(
            'message' => 'Detaylar başarıyla kaydedildi.',
            'kayit_id' => $kayit_id,
            'detaylar' => array(
                'dolsuz_yumurta' => $detaylar['detay_dolsuz_yumurta'],
                'cikan_civciv' => $detaylar['detay_cikan_civciv'],
                'notlar' => $detaylar['detay_notlar']
            )
        ));
    } else {
        wp_send_json_error('Detay kaydetme başarısız oldu veya kayıt bulunamadı.');
    }
    exit;
}
add_action('wp_ajax_km_kaydet_detaylar', 'km_kaydet_detaylar');

// Yeni kayıt formu gönderimini işle (admin-post.php üzerinden)
function km_yeni_kayit_handler() {
    global $wpdb;

    // Nonce kontrolü
    if (!isset($_POST['km_nonce']) || !wp_verify_nonce($_POST['km_nonce'], 'km_yeni_kayit_nonce')) {
        wp_die('Güvenlik doğrulaması başarısız.');
    }

    // Kullanıcı kontrolü
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(home_url('/kuluckamatik/')));
        exit;
    }

    // Form verilerini al ve temizle
    $tur = isset($_POST['hayvan_turu']) ? sanitize_text_field($_POST['hayvan_turu']) : null;
    $baslangic_tarihi = isset($_POST['kulucka_baslangic']) ? sanitize_text_field($_POST['kulucka_baslangic']) : null;
    $yumurta_sayisi = isset($_POST['yumurta_sayisi']) ? intval($_POST['yumurta_sayisi']) : 0;
    $not = isset($_POST['not']) ? sanitize_textarea_field($_POST['not']) : '';

    // Gerekli alanlar boş mu kontrol et
    if (empty($tur) || empty($baslangic_tarihi) || $yumurta_sayisi <= 0) {
        wp_redirect(add_query_arg('kayit_durum', 'hata', home_url('/kuluckamatik/')));
        exit;
    }

    // Kuluçka verilerini al
    $kulucka_data = get_kulucka_data();
    $tur_data = $kulucka_data[$tur] ?? null;

    if (!$tur_data) {
        wp_redirect(add_query_arg('kayit_durum', 'hata', home_url('/kuluckamatik/')));
        exit;
    }

    $kulucka_suresi = $tur_data['sure'];

    // Çıkım tarihini hesapla
    $baslangic_timestamp = strtotime($baslangic_tarihi);
    $cikim_timestamp = strtotime("+$kulucka_suresi days", $baslangic_timestamp);
    $cikim_tarihi = date('Y-m-d', $cikim_timestamp);

    // Benzersiz ID oluştur
    $yeni_kayit_id = uniqid('km_');
    $kullanici_id = get_current_user_id();

    // Şu anki zaman
    $eklenme_tarihi = current_time('mysql');

    // Yeni kaydı tabloya ekle
    $table_name = $wpdb->prefix . 'kulucka_kayitlar';
    $sonuc = $wpdb->insert(
        $table_name,
        array(
            'id' => $yeni_kayit_id,
            'user_id' => $kullanici_id,
            'tur' => $tur,
            'baslangic' => $baslangic_tarihi,
            'cikim' => $cikim_tarihi,
            'sure' => $kulucka_suresi,
            'yumurta_sayisi' => $yumurta_sayisi,
            'not' => $not,
            'detay_dolsuz_yumurta' => 0,
            'detay_cikan_civciv' => 0,
            'detay_notlar' => '',
            'eklenme_tarihi' => $eklenme_tarihi
        ),
        array(
            '%s', // id
            '%d', // user_id
            '%s', // tur
            '%s', // baslangic
            '%s', // cikim
            '%d', // sure
            '%d', // yumurta_sayisi
            '%s', // not
            '%d', // detay_dolsuz_yumurta
            '%d', // detay_cikan_civciv
            '%s', // detay_notlar
            '%s'  // eklenme_tarihi
        )
    );

    if ($sonuc !== false) {
        wp_redirect(add_query_arg('kayit_durum', 'basarili', home_url('/kuluckamatik/')));
    } else {
        wp_redirect(add_query_arg('kayit_durum', 'hata', home_url('/kuluckamatik/')));
    }
    exit;
}
// admin-post.php hook'ları (giriş yapmış ve yapmamış kullanıcılar için)
add_action('admin_post_km_yeni_kayit', 'km_yeni_kayit_handler');
add_action('admin_post_nopriv_km_yeni_kayit', 'km_yeni_kayit_handler'); // Giriş yapmamışsa zaten başta kontrol ediliyor


// URL yönlendirmelerini değiştir
function km_giris_url_degistir($login_url, $redirect, $force_reauth) {
    // Eğer kuluckamatik sayfasından geliyorsa
    if (strpos($redirect, 'kuluckamatik') !== false) {
        $login_url = add_query_arg('redirect_to', home_url('/kuluckamatik/'), $login_url);
    }
    return $login_url;
}
add_filter('login_url', 'km_giris_url_degistir', 10, 3);

// Kısa kodla formu ve kayıtları göster
function km_form_shortcode() {
    global $wpdb;
    // Gerekli verileri al
    $kulucka_data = get_kulucka_data();
    $kullanici_id = get_current_user_id();

    // Kayıtları yeni tablodan çek
    $table_name = $wpdb->prefix . 'kulucka_kayitlar';
    $kayitlar = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY baslangic DESC", $kullanici_id),
        ARRAY_A
    );
    if (empty($kayitlar)) $kayitlar = array();

    // Eski veri yapısına uyumlu hale getir (detaylar için)
    foreach ($kayitlar as &$kayit) {
        $kayit['detaylar'] = array(
            'dolsuz_yumurta' => isset($kayit['detay_dolsuz_yumurta']) ? intval($kayit['detay_dolsuz_yumurta']) : 0,
            'cikan_civciv' => isset($kayit['detay_cikan_civciv']) ? intval($kayit['detay_cikan_civciv']) : 0,
            'notlar' => isset($kayit['detay_notlar']) ? $kayit['detay_notlar'] : ''
        );
    }
    unset($kayit);

    // Verileri şablona gönder
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/form.php';
    return ob_get_clean();
}
add_shortcode('kuluckamatik_form', 'km_form_shortcode');
