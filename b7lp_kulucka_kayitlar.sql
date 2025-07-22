-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 27 May 2025, 18:48:28
-- Sunucu sürümü: 8.0.42
-- PHP Sürümü: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `bfjguven_wp603`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `b7lp_kulucka_kayitlar`
--

CREATE TABLE `b7lp_kulucka_kayitlar` (
  `id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `tur` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `baslangic` date NOT NULL,
  `cikim` date NOT NULL,
  `sure` int NOT NULL,
  `yumurta_sayisi` int NOT NULL,
  `not` text COLLATE utf8mb4_unicode_ci,
  `detay_dolsuz_yumurta` int DEFAULT '0',
  `detay_cikan_civciv` int DEFAULT '0',
  `detay_notlar` text COLLATE utf8mb4_unicode_ci,
  `eklenme_tarihi` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `b7lp_kulucka_kayitlar`
--

INSERT INTO `b7lp_kulucka_kayitlar` (`id`, `user_id`, `tur`, `baslangic`, `cikim`, `sure`, `yumurta_sayisi`, `not`, `detay_dolsuz_yumurta`, `detay_cikan_civciv`, `detay_notlar`, `eklenme_tarihi`) VALUES
('km_67fcb315b3eae', 88, 'Tavuk', '2025-04-06', '2025-04-27', 21, 66, '', 6, 60, '', '2025-04-14 10:02:45'),
('km_68298d32a469e', 1, 'Tavuk', '2025-05-18', '2025-06-08', 21, 100, '', 0, 0, '', '2025-05-18 10:33:06'),
('km_68298d3346058', 1, 'Tavuk', '2025-05-18', '2025-06-08', 21, 100, '', 0, 0, '', '2025-05-18 10:33:07'),
('km_682a21b7de339', 117, 'Keklik', '2025-05-07', '2025-05-31', 24, 14, '', 0, 0, '', '2025-05-18 21:06:47'),
('km_682b76d56b586', 117, 'Bıldırcın', '2025-05-07', '2025-05-24', 17, 42, '', 0, 0, '', '2025-05-19 21:22:13');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `b7lp_kulucka_kayitlar`
--
ALTER TABLE `b7lp_kulucka_kayitlar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
