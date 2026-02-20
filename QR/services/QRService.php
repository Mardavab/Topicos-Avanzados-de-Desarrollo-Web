<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;

class QRService {

    public function generate($content, $size = 300, $errorCorrection = 'M') {

        if ($size < 100 || $size > 1000) {
            throw new Exception("El tamaño debe estar entre 100 y 1000 px");
        }

        // Mapear niveles de corrección
        $levels = [
            'L' => ErrorCorrectionLevel::Low,
            'M' => ErrorCorrectionLevel::Medium,
            'Q' => ErrorCorrectionLevel::Quartile,
            'H' => ErrorCorrectionLevel::High
        ];

        $qrCode = new QrCode(
            data: $content,
            size: $size,
            margin: 10,
            errorCorrectionLevel: $levels[$errorCorrection] ?? ErrorCorrectionLevel::Medium
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getString();
    }

    public function buildWifi($ssid, $password, $type) {
        return "WIFI:T:$type;S:$ssid;P:$password;;";
    }

    public function buildGeo($lat, $lng) {

        if (!is_numeric($lat) || !is_numeric($lng)) {
            throw new Exception("Coordenadas inválidas");
        }

        return "geo:$lat,$lng";
    }
}