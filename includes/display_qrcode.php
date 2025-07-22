<?php
// Display today's QR image from the generated file
$filename = '../assets/qrcodes/' . date('Ymd') . '.png';

if (file_exists($filename)) {
    header('Content-Type: image/png');
    readfile($filename);
    exit;
} else {
    http_response_code(404);
    echo "QR code image not found.";
}
