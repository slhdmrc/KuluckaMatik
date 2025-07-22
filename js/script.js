// script.js
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM fully loaded, initializing scripts');
    
    // OLUŞTUR butonu işlevselliği
    const olusturButon = document.getElementById('olustur-buton');
    const formContainer = document.getElementById('kuluckamatik-form-container');

    if (olusturButon && formContainer) {
        console.log('Found olustur button and form container');
        olusturButon.addEventListener('click', function () {
            console.log('Button clicked, current display:', formContainer.style.display);
            if (formContainer.style.display === 'none') {
                formContainer.style.display = 'block';
                this.textContent = 'GİZLE';
            } else {
                formContainer.style.display = 'none';
                this.textContent = 'KAYIT OLUŞTUR';
            }
        });
    } else {
        console.log('Button or container not found', {
            olusturButon: olusturButon,
            formContainer: formContainer
        });
    }

    // Form doğrulama
    const form = document.querySelector('#kuluckamatik-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            const tarih = form.querySelector('input[name="kulucka_baslangic"]').value;
            const tur = form.querySelector('select[name="hayvan_turu"]').value;
            const yumurtaSayisi = form.querySelector('input[name="yumurta_sayisi"]').value;

            if (!tarih || !tur || !yumurtaSayisi) {
                alert("Lütfen tarih, tür ve yumurta sayısını giriniz.");
                e.preventDefault();
            }
        });
    }

    // Genel olay dinleyici (Silme, Detay Toggle, Detay Kaydet)
    document.addEventListener('click', function (e) {
        const target = e.target;

        // Silme Butonu Kontrolü
        const deleteButton = target.closest('.kulucka-sil');
        if (deleteButton) {
            e.preventDefault();
            console.log('Delete button clicked', deleteButton);

            const tur = deleteButton.getAttribute('data-tur');
            const kayitId = deleteButton.getAttribute('data-kayit-id');

            console.log('Delete parameters:', { tur, kayitId });

            if (confirm(`${tur} için kaydı silmek istediğinize emin misiniz?`)) {
                kayitSil(tur, kayitId);
            }
            return; // Başka bir işlem yapma
        }

        // Detay Toggle Butonu Kontrolü
        const toggleButton = target.closest('.detay-toggle-buton');
        if (toggleButton) {
            e.preventDefault();
            const targetId = toggleButton.getAttribute('data-target');
            const detayBolumu = document.getElementById(targetId);
            if (detayBolumu) {
                if (detayBolumu.style.display === 'none' || detayBolumu.style.display === '') {
                    detayBolumu.style.display = 'block';
                    toggleButton.textContent = 'Detayları Gizle';
                } else {
                    detayBolumu.style.display = 'none';
                    toggleButton.textContent = 'Detayları Yönet';
                }
            }
            return; // Başka bir işlem yapma
        }

        // Detay Kaydet Butonu Kontrolü
        const saveDetailButton = target.closest('.kaydet-detay-buton');
        if (saveDetailButton) {
            e.preventDefault();
            const kayitId = saveDetailButton.getAttribute('data-kayit-id');
            const detayBolumu = saveDetailButton.closest('.detaylar-bolumu');
            if (detayBolumu && kayitId) {
                const dolsuzInput = detayBolumu.querySelector('input[name="dolsuz_yumurta"]');
                const cikanInput = detayBolumu.querySelector('input[name="cikan_civciv"]');
                const notlarInput = detayBolumu.querySelector('textarea[name="notlar"]');
                const mesajDiv = detayBolumu.querySelector('.detay-mesaj');

                const detaylar = {
                    kayit_id: kayitId,
                    dolsuz_yumurta: dolsuzInput ? parseInt(dolsuzInput.value) || 0 : 0,
                    cikan_civciv: cikanInput ? parseInt(cikanInput.value) || 0 : 0,
                    notlar: notlarInput ? notlarInput.value : ''
                };

                // Basit doğrulama (isteğe bağlı)
                // Yüklenen yumurta sayısı
                let toplamYumurta = 0;
                const kart = document.getElementById(`kayit-${kayitId}`);
                if (kart) {
                    const pList = kart.querySelectorAll('p');
                    pList.forEach(function(p) {
                        if (p.textContent.includes('Yüklenen Yumurta:')) {
                            toplamYumurta = parseInt(p.textContent.replace(/\D/g, '')) || 0;
                        }
                    });
                }
                if (toplamYumurta > 0 && detaylar.cikan_civciv > toplamYumurta) {
                    mesajDiv.textContent = 'Çıkan civciv sayısı yüklenen yumurta sayısından büyük olamaz.';
                    mesajDiv.className = 'detay-mesaj hata-mesaj';
                    mesajDiv.style.display = 'block';
                    setTimeout(() => mesajDiv.style.display = 'none', 3000);
                    return;
                }


                kaydetDetaylar(detaylar, mesajDiv);
            }
            return; // Başka bir işlem yapma
        }
    });


    // --- Eski Civciv Sayısı Değişiklik Dinleyicisi Kaldırıldı ---
    /*
    document.addEventListener('change', function (e) {
        // ... eski kod ...
    });
    */

    // AJAX ile kayıt silme fonksiyonu
    function kayitSil(tur, kayitId) {
        console.log('KayitSil function called with:', { tur, kayitId });
        
        // Mesaj divini temizle
        const mesajDiv = document.getElementById('km-mesaj');
        mesajDiv.style.display = 'none';
        mesajDiv.innerHTML = '';
        mesajDiv.className = '';

        // AJAX isteği gönder
        const xhr = new XMLHttpRequest();
        xhr.open('POST', kmAjax.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function () {
            console.log('AJAX response received:', xhr.status, xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Parsed response:', response);

                    if (response.success) {
                        // Başarılı silme işlemi
                        const kayitDiv = document.getElementById(`kayit-${kayitId}`);
                        if (kayitDiv) {
                            kayitDiv.remove();
                            console.log('Card removed successfully');

                            // Eğer başka kayıt kalmadıysa "Hiç kayıt bulunamadı" mesajını göster
                            const kalanKayitlar = document.querySelectorAll('.kulucka-kart');
                            if (kalanKayitlar.length === 0) {
                                const kayitlarDiv = document.getElementById('kulucka-kayitlar');
                                const bosKayitMesaji = document.createElement('p');
                                bosKayitMesaji.id = 'bos-kayit-mesaji';
                                bosKayitMesaji.textContent = 'Hiç kayıt bulunamadı.';
                                kayitlarDiv.appendChild(bosKayitMesaji);
                                console.log('No records message added');
                            }
                        } else {
                            console.error('Card element not found:', `kayit-${kayitId}`);
                        }

                        // Başarı mesajı göster
                        mesajDiv.innerHTML = response.data.message || 'Kayıt başarıyla silindi.';
                        mesajDiv.className = 'basari-mesaj';
                        mesajDiv.style.display = 'block';

                        // 3 saniye sonra mesajı kaldır
                        setTimeout(function () {
                            mesajDiv.style.display = 'none';
                        }, 3000);
                    } else {
                        // Hata mesajı göster
                        console.error('Error response from server:', response.data);
                        mesajDiv.innerHTML = response.data || 'Silme işlemi sırasında bir hata oluştu.';
                        mesajDiv.className = 'hata-mesaj';
                        mesajDiv.style.display = 'block';
                    }
                } catch (e) {
                    console.error('JSON parse hatası:', e, xhr.responseText);
                    mesajDiv.innerHTML = 'Sunucu yanıtı işlenirken bir hata oluştu.';
                    mesajDiv.className = 'hata-mesaj';
                    mesajDiv.style.display = 'block';
                }
            } else {
                console.error('HTTP hatası:', xhr.status, xhr.statusText);
                mesajDiv.innerHTML = 'Sunucu isteği işlerken bir hata oluştu.';
                mesajDiv.className = 'hata-mesaj';
                mesajDiv.style.display = 'block';
            }
        };

        xhr.onerror = function () {
            console.error('Ağ hatası');
            mesajDiv.innerHTML = 'Ağ hatası oluştu.';
            mesajDiv.className = 'hata-mesaj';
            mesajDiv.style.display = 'block';
        };

        // Form verilerini hazırla ve gönder
        const formData = `action=km_sil_kayit&tur=${encodeURIComponent(tur)}&kayit_id=${encodeURIComponent(kayitId)}&nonce=${encodeURIComponent(kmAjax.nonce)}`;
        console.log('Sending form data:', formData);
        xhr.send(formData);
    }

    // AJAX ile kuluçka detaylarını kaydetme fonksiyonu
    function kaydetDetaylar(detaylar, mesajDiv) {
        console.log('kaydetDetaylar called with:', detaylar);

        // Mesaj divini temizle
        mesajDiv.style.display = 'none';
        mesajDiv.innerHTML = '';
        mesajDiv.className = 'detay-mesaj';

        // AJAX isteği gönder
        const xhr = new XMLHttpRequest();
        xhr.open('POST', kmAjax.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function () {
            console.log('Detay Kaydet AJAX response:', xhr.status, xhr.responseText);

            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Parsed detay response:', response);

                    if (response.success) {
                        // Başarılı kaydetme
                        mesajDiv.innerHTML = response.data.message || 'Detaylar başarıyla kaydedildi.';
                        mesajDiv.className = 'detay-mesaj basari-mesaj';
                        mesajDiv.style.display = 'block';

                        // 3 saniye sonra mesajı kaldır
                        setTimeout(function () {
                            mesajDiv.style.display = 'none';
                        }, 3000);

                        // İsteğe bağlı: Kaydedilen değerleri inputlara tekrar yazdır (genelde gerekmez)
                        // const bolum = document.getElementById(`detaylar-${detaylar.kayit_id}`);
                        // if(bolum) {
                        //     bolum.querySelector('input[name="dolsuz_yumurta"]').value = response.data.detaylar.dolsuz_yumurta;
                        //     // ... diğer inputlar
                        // }

                    } else {
                        // Hata mesajı göster
                        console.error('Error response from server (detay):', response.data);
                        mesajDiv.innerHTML = response.data || 'Detay kaydetme sırasında bir hata oluştu.';
                        mesajDiv.className = 'detay-mesaj hata-mesaj';
                        mesajDiv.style.display = 'block';
                    }
                } catch (e) {
                    console.error('JSON parse hatası (detay):', e, xhr.responseText);
                    mesajDiv.innerHTML = 'Sunucu yanıtı işlenirken bir hata oluştu.';
                    mesajDiv.className = 'detay-mesaj hata-mesaj';
                    mesajDiv.style.display = 'block';
                }
            } else {
                console.error('HTTP hatası (detay):', xhr.status, xhr.statusText);
                mesajDiv.innerHTML = 'Sunucu isteği işlerken bir hata oluştu.';
                mesajDiv.className = 'detay-mesaj hata-mesaj';
                mesajDiv.style.display = 'block';
            }
        };

        xhr.onerror = function () {
            console.error('Ağ hatası (detay)');
            mesajDiv.innerHTML = 'Ağ hatası oluştu.';
            mesajDiv.className = 'detay-mesaj hata-mesaj';
            mesajDiv.style.display = 'block';
        };

        // Form verilerini hazırla ve gönder
        const formData = `action=km_kaydet_detaylar&kayit_id=${encodeURIComponent(detaylar.kayit_id)}&dolsuz_yumurta=${encodeURIComponent(detaylar.dolsuz_yumurta)}&cikan_civciv=${encodeURIComponent(detaylar.cikan_civciv)}&notlar=${encodeURIComponent(detaylar.notlar)}&nonce=${encodeURIComponent(kmAjax.nonce)}`;
        console.log('Sending detay form data:', formData);
        xhr.send(formData);
    }


     // --- Eski guncelleCivciv Fonksiyonu Kaldırıldı ---
    /*
    function guncelleCivciv(kayitId, civcivSayisi, yumurtaSayisi) {
        // ... eski kod ...
    }
    */

    // Yardımcı: Element içeriğinde metin arama (jQuery :contains benzeri)
    // Not: Bu basit bir implementasyondur, daha karmaşık durumlar için geliştirilebilir.
    function containsText(selector, text) {
        const elements = document.querySelectorAll(selector);
        for (let i = 0; i < elements.length; i++) {
            if (elements[i].textContent.includes(text)) {
                return elements[i];
            }
        }
        return null;
    }
     // jQuery'nin :contains seçicisini taklit etmek için prototip ekleme (opsiyonel)
     if (!Element.prototype.matches) {
         Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
     }
     if (!Element.prototype.closest) {
         Element.prototype.closest = function(s) {
             var el = this;
             do {
                 if (Element.prototype.matches.call(el, s)) return el;
                 el = el.parentElement || el.parentNode;
             } while (el !== null && el.nodeType === 1);
             return null;
         };
     }
     // Basit :contains benzeri işlevsellik (yukarıdaki yardımcı fonksiyonu kullanır)
     // Kullanım: document.querySelector('p:contains("Metin")') yerine containsText('p', 'Metin')
    // Notlar ve kısa textarea'lar için auto-grow
    function autoGrowTextarea(textarea) {
        textarea.style.height = '28px';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    document.querySelectorAll('textarea[name="not"], textarea[name="notlar"]').forEach(function (ta) {
        ta.addEventListener('input', function () {
            autoGrowTextarea(this);
        });
        // İlk yüklemede de uygula
        autoGrowTextarea(ta);
    });

    // Ana formda çıkan civciv sayısı kontrolü (gerekirse eklenebilir)
});
