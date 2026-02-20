<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;

class QRService {

    public function generate(string $content, int $size = 300, string $errorCorrection = 'M'): string {

        if (trim($content) === '') {
            throw new Exception("El contenido no puede estar vacío");
        }

        if ($size < 100 || $size > 1000) {
            throw new Exception("El tamaño debe estar entre 100 y 1000 px");
        }

        $errorCorrectionLevel = $this->resolveErrorCorrection($errorCorrection);

        $qrCode = new QrCode(
            data: $content,
            size: $size,
            margin: 10,
            errorCorrectionLevel: $errorCorrectionLevel
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getString();
    }

    private function resolveErrorCorrection(string $level): ErrorCorrectionLevel {

        return match (strtoupper($level)) {
            'L' => ErrorCorrectionLevel::Low,
            'M' => ErrorCorrectionLevel::Medium,
            'Q' => ErrorCorrectionLevel::Quartile,
            'H' => ErrorCorrectionLevel::High,
            default => ErrorCorrectionLevel::Medium
        };
    }

    public function buildWifi(string $ssid, string $password, string $type): string {

        if ($ssid === '' || $type === '') {
            throw new Exception("Datos WiFi incompletos");
        }

        return sprintf(
            "WIFI:T:%s;S:%s;P:%s;;",
            addslashes($type),
            addslashes($ssid),
            addslashes($password)
        );
    }

    public function buildGeo($lat, $lng): string {

        if (!is_numeric($lat) || !is_numeric($lng)) {
            throw new Exception("Coordenadas inválidas");
        }

        return sprintf("geo:%s,%s", $lat, $lng);
    }
}
