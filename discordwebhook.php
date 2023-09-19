<?php
$webhookUrl = 'hook';

// Log dosyasının adı ve yolu
$tarih = date('d.m.Y');
$logFilePath = 'logs/' . $tarih . '_ip_log.txt';

// Log dosyasını satır satır oku
$lines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$lineCount = count($lines);

// Satır sayısı 10'a tam bölünebiliyorsa yalnızca son 10 satırı Discord Webhook'a gönder
if ($lineCount % 10 === 0) {
    $lastTenLines = array_slice($lines, -10);
    $message = implode("\n", $lastTenLines);

    $postData = json_encode(array('content' => $message));
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        )
    );
    $context = stream_context_create($options);
    $response = file_get_contents($webhookUrl, false, $context);

    if ($response === false) {
        echo "Hata: Discord Webhook'a gönderirken bir hata oluştu.\n";
    } else {
        echo "Son 10 satır Discord'a gönderildi.\n";
    }
} else {
    echo "Satır sayısı 10'a tam bölünebilmiyor, bu nedenle işlem yapılmadı.\n";
}
?>
