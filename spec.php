<?php
session_start();

// Oturum süresi dolmuşsa veya oturum başlatılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION["giris"]) || $_SESSION["giris"] !== true) {
    header("Location: giris.php");
    exit();
}

$klasor = $_SERVER['DOCUMENT_ROOT'] . '/logs';

// Sayfa başlangıcında, mevcut seçili log dosyasını kontrol edin ve bir cookie'ye kaydedin
if (isset($_POST['log_dosyasi'])) {
    $seciliDosya = $_POST['log_dosyasi'];
    // Log dosyası değiştiğinde sayfa parametresini sıfırla
    unset($_GET['sayfa']);
    setcookie('secili_dosya', $seciliDosya, time() + 3600); // Bir saatlik süreyle cookie'yi sakla
} elseif (isset($_COOKIE['secili_dosya'])) {
    $seciliDosya = $_COOKIE['secili_dosya'];
}

// Klasördeki tüm dosyaları listele
$dosyalar = scandir($klasor);

// "." ve ".." özel dosyalarını kaldır
$dosyalar = array_diff($dosyalar, array('.', '..'));

// Önceki filtreleme verilerini al
$ipFiltresi = isset($_POST['ip']) ? $_POST['ip'] : (isset($_GET['ip']) ? $_GET['ip'] : ''); // URL'den de kontrol ed
$kelimeFiltresi = isset($_POST['kelime']) ? $_POST['kelime'] : (isset($_GET['kelime']) ? $_GET['kelime'] : ''); // URL'den de kontrol ed
$uuidFiltresi = isset($_POST['uuid']) ? $_POST['uuid'] : (isset($_GET['uuid']) ? $_GET['uuid'] : ''); // UUID filtresini al

// Benzersiz kullanıcıları saklamak için bir dizi oluşturun ve başlatın
$uniqueUsers = array();

// Sonuç Sayısı
if (isset($_POST['sonuc_sayisi'])) {
    $sonucSayisi = max(1, intval($_POST['sonuc_sayisi'])); // En az 1 olacak şekilde tam sayıya dönüştürün
} elseif (isset($_GET['sonuc_sayisi'])) {
    $sonucSayisi = max(1, intval($_GET['sonuc_sayisi'])); // En az 1 olacak şekilde tam sayıya dönüştürün
} else {
    $sonucSayisi = 100; // Varsayılan değer
}

// Filtreleme formunu oluştur
echo '<form method="POST" action="">';
echo '<label for="log_dosyasi">Log Dosyası Seç:</label>';
echo '<select name="log_dosyasi" id="log_dosyasi">';
foreach ($dosyalar as $dosya) {
    $selected = ($dosya === $seciliDosya) ? 'selected' : '';
    echo '<option value="' . $dosya . '"' . $selected . '>' . $dosya . '</option>';
}
echo '</select><br>';

// IP filtresi
echo '<label for="ip">IP Adresi:</label>';
echo '<input type="text" name="ip" id="ip" placeholder="IP adresi" value="' . $ipFiltresi . '"><br>';

// Kelime filtresi
echo '<label for="kelime">Kelime Filtresi:</label>';
echo '<input type="text" name="kelime" id="kelime" placeholder="Kelime" value="' . $kelimeFiltresi . '"><br>';

// Sonuç Sayısı Form Alanı
echo '<label for="sonuc_sayisi">Sonuç Sayısı:</label>';
echo '<input type="number" name="sonuc_sayisi" id="sonuc_sayisi" min="1" value="' . $sonucSayisi . '"><br>';

// UUID filtresi
echo '<label for="uuid">UUID Filtresi:</label>';
echo '<input type="text" name="uuid" id="uuid" placeholder="UUID" value="' . $uuidFiltresi . '"><br>';

// Benzersiz kullanıcıları filtreleme kutusu
echo '<label for="benzersiz_kullanicilar">Benzersiz Kullanıcılar:</label>';
echo '<input type="checkbox" name="benzersiz_kullanicilar" id="benzersiz_kullanicilar" value="1"';
if (isset($_POST['benzersiz_kullanicilar']) && $_POST['benzersiz_kullanicilar'] == '1') {
    echo ' checked';
}
echo '><br>';

// Filtreleme düğmesi
echo '<button type="submit">Filtrele</button>';
echo '</form>';

// Dosya içeriğini görüntüleme
if (isset($seciliDosya) && $seciliDosya !== '') {
    $seciliDosya = $klasor . '/' . $seciliDosya;
    $logIcerik = file($seciliDosya, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (file_exists($seciliDosya) && is_file($seciliDosya)) {
        $satirSayisi = count(file($seciliDosya));
        echo '<p>Satır Sayısı: ' . $satirSayisi . '</p>';

        echo '<h2>Dosya İçeriği: ' . basename($seciliDosya) . '</h2>';
        echo '<table>';
        echo "<tr><th>Satır</th><th>Uuid</th><th>Saat</th><th>İp Adresi</th><th>Eski Sayfa</th><th>Yeni Sayfa</th><th>Ülke</th><th></th></tr>";

        $goruntulenecekSatirlar = [];

        foreach ($logIcerik as $satir) {
            $veriler = explode(' - ', $satir);
            $logIP = isset($veriler[2]) ? trim($veriler[2]) : '';
            $logSayfa = trim(end($veriler));
            $logUUID = isset($veriler[1]) ? trim($veriler[1]) : ''; // UUID değerini al

            // Kriterlere göre filtreleme yapın
            $uuidFiltresiUygun = empty($uuidFiltresi) || strpos($logUUID, $uuidFiltresi) !== false; // UUID filtresini kontrol et

            if ((empty($ipFiltresi) || strpos($logIP, $ipFiltresi) !== false) &&
                (empty($kelimeFiltresi) || strpos($logSayfa, $kelimeFiltresi) !== false) &&
                $uuidFiltresiUygun) { // UUID filtresini ekleyin
                $entryData = explode(' - ', $satir);
                $line = $entryData[0];
                $uuid = $entryData[1];
                $time = $entryData[2];
                $ip = $entryData[3];
                $previousPage = $entryData[4];
                $currentPage = $entryData[5];
                $region = $entryData[6];

                // Benzersiz kullanıcı kontrolü
                $isUnique = true;

                foreach ($uniqueUsers as $user) {
                    $userEntryData = explode(' - ', $user);
                    $userIP = isset($userEntryData[3]) ? trim($userEntryData[3]) : '';
                    $userUUID = isset($userEntryData[1]) ? trim($userEntryData[1]) : '';

                    if ($userIP === $ip || $userUUID === $uuid) {
                        $isUnique = false;
                        break;
                    }
                }

                if ($isUnique) {
                    $uniqueUsers[] = $satir;
                }

                $goruntulenecekSatirlar[] = $satir;
            }
        }

        // Eğer "Benzersiz Kullanıcılar" kutusu işaretlendi ise sadece benzersiz kullanıcıları listele
        if (isset($_POST['benzersiz_kullanicilar']) && $_POST['benzersiz_kullanicilar'] == '1') {
            $goruntulenecekSatirlar = $uniqueUsers;
        }

        // Sonuçları sayfalandırmak için gerekli değişkenleri tanımlayın
        $toplamSonucSayisi = count($goruntulenecekSatirlar); // Toplam sonuç sayısı
        $toplamSayfa = ceil($toplamSonucSayisi / $sonucSayisi); // Toplam sayfa sayısı
        $sayfa = isset($_GET['sayfa']) ? max(1, min($_GET['sayfa'], $toplamSayfa)) : 1; // Geçerli sayfa numarası

        // Sonuçları sayfaya göre dilimleyin
        $baslangicIndeksi = ($sayfa - 1) * $sonucSayisi;
        $bitisIndeksi = $baslangicIndeksi + $sonucSayisi;

        // Sadece mevcut sayfa için sonuçları alın
        $sayfaSonuclari = array_slice($goruntulenecekSatirlar, $baslangicIndeksi, $sonucSayisi);

        // Sonuçları görüntüle
        foreach ($sayfaSonuclari as $satir) {
            $entryData = explode(' - ', $satir);
            $line = $entryData[0];
            $uuid = $entryData[1];
            $time = $entryData[2];
            $ip = $entryData[3];
            $previousPage = $entryData[4];
            $currentPage = $entryData[5];
            $region = $entryData[6];
            echo "<tr>";
            echo "<td style='padding-right: 15px;'>$line</td>";
            echo "<td style='padding-right: 15px;'>$uuid</td>";
            echo "<td style='padding-right: 15px;'>$time</td>";
            echo "<td style='padding-right: 15px;'>$ip</td>";
            echo "<td style='padding-right: 15px;'>$previousPage</td>";
            echo "<td style='padding-right: 15px;'>$currentPage</td>";
            echo "<td style='padding-right: 15px;'>$region</td>";

            echo "<td><a href='https://check-host.net/ip-info?host=$ip' target='_blank'>IP Detayları</a></td>";
            echo "</tr>";
        }

        echo '</table>';

        // Sayfalandırma bağlantıları
        echo '<div>';
        if ($toplamSayfa > 1) {
            echo 'Sayfalar: ';
            for ($i = 1; $i <= $toplamSayfa; $i++) {
                // URL'deki filtreleri de tutarak sayfa bağlantılarını oluştur
                $queryParameters = http_build_query([
                    'log_dosyasi' => $seciliDosya,
                    'ip' => $ipFiltresi,
                    'kelime' => $kelimeFiltresi,
                    'sayfa' => $i,
                    'sonuc_sayisi' => $sonucSayisi,
                    'uuid' => $uuidFiltresi,
                    'benzersiz_kullanicilar' => isset($_POST['benzersiz_kullanicilar']) ? '1' : '0',
                ]);
                echo '<a href="?' . $queryParameters . '">' . $i . '</a> ';
            }
        }
        echo '</div>';
    }
}

// HTML sonu
echo '</body>';
echo '</html>';
?>
