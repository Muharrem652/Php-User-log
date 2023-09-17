<?php
// Oturum süresini 1 saat (3600 saniye) olarak ayarla
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

session_start();

// Şifrenizi burada belirleyin (örnek şifre: "12345").
$dogru_sifre = "12345";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $girilen_sifre = $_POST["sifre"];
    
    if ($girilen_sifre == $dogru_sifre) {
        $_SESSION["giris"] = true; // Oturumu başlat
        header("Location: spec.php"); // Şifre doğruysa korumalı sayfaya yönlendir
        exit();
    } else {
        echo "Hatalı şifre. Lütfen tekrar deneyin.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Giriş Sayfası</title>
</head>
<body>
    <h1>Hoş geldiniz!</h1>
    <p>Lütfen şifreyi girin:</p>
    <form action="" method="POST">
        <input type="password" name="sifre">
        <input type="submit" value="Giriş">
    </form>
</body>
</html>
