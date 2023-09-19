<?php
session_start(); // Oturumu başlat

// Kullanıcıya benzersiz bir ID atayalım veya kullanıcı adını kullanalım
if (isset($_SESSION['username'])) {
    $user_id = $_SESSION['username'];
} else {
    if (isset($_COOKIE['user_id'])) {
        $user_id = $_COOKIE['user_id'];
    } else {
        // Benzersiz bir UUID oluştur
        $user_id = uniqid();
        
        // UUID'yi çerez olarak sakla (365 gün boyunca geçerli)
        setcookie('user_id', $user_id, time() + (86400 * 365), '/');
    }
}

// Kullanıcının şu anki sayfa URL'sini al
$current_page = $_SERVER['PHP_SELF'];

// Önceki sayfa bilgisini al (oturumda saklanacak)
$previous_page = isset($_SESSION['current_page']) ? $_SESSION['current_page'] : '';

// Şu anki sayfayı oturumda sakla
$_SESSION['current_page'] = $current_page;

// Ziyaretçinin IP adresini al
$ip = $_SERVER['REMOTE_ADDR'];

// Ziyaretçinin tarihini al
$tarih = date('d.m.Y');

// Tarih bazlı dosya adını oluştur
$logDosya = 'logs/' . $tarih . '_ip_log.txt';

// Dosyadaki son sıra numarasını al (eğer dosya boşsa 0 olarak varsayılır)
$siraNumarasi = file_exists($logDosya) ? count(file($logDosya)) + 1 : 1;

// IP adresi, benzersiz kullanıcı ID, önceki sayfa ve şu anki sayfa bilgisini dosyaya ekle
file_put_contents($logDosya, $siraNumarasi . " - " . $user_id . " - "  . date('H:i:s') . " - " . $ip . " - " . $previous_page . " - " . $current_page . PHP_EOL, FILE_APPEND);

// Kullanıcıya önceki ve şu anki sayfa bilgisini gösterin (isteğe bağlı)
echo "Önceki Sayfa: " . $previous_page . "<br>";
echo "Şu Anki Sayfa: " . $current_page;

// Discord Webhook URL'si
require 'discordwebhook.php'
?>
