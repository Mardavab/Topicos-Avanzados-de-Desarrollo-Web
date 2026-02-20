<?php

require_once __DIR__ . '/../services/QRService.php';

class QRController {

    private QRService $service;

    public function __construct() {
        $this->service = new QRService();
    }

    public function generate(): void {

        try {

            $data = $this->getRequestData();

            $type = $data['type'] ?? 'text';
            $size = (int) ($data['size'] ?? 300);
            $errorCorrection = $data['errorCorrection'] ?? 'M';

            $content = $this->resolveContent($type, $data);

            $qr = $this->service->generate($content, $size, $errorCorrection);

            header("Content-Type: image/png");
            echo $qr;

        } catch (Throwable $e) {

            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode([
                "error" => $e->getMessage()
            ]);
        }
    }

    private function getRequestData(): array {

        $input = json_decode(file_get_contents("php://input"), true);

        if (!is_array($input)) {
            throw new Exception("JSON inválido");
        }

        return $input;
    }

    private function resolveContent(string $type, array $data): string {

        return match ($type) {

            'wifi' => $this->buildWifiContent($data),

            'geo'  => $this->buildGeoContent($data),

            'url'  => $this->validateUrl($data['content'] ?? ''),

            default => $data['content'] ?? ''
        };
    }

    private function buildWifiContent(array $data): string {

        if (empty($data['ssid']) || !isset($data['password']) || empty($data['encryption'])) {
            throw new Exception("Datos WiFi incompletos");
        }

        return $this->service->buildWifi(
            $data['ssid'],
            $data['password'],
            $data['encryption']
        );
    }

    private function buildGeoContent(array $data): string {

        if (!isset($data['lat'], $data['lng'])) {
            throw new Exception("Coordenadas inválidas");
        }

        return $this->service->buildGeo(
            $data['lat'],
            $data['lng']
        );
    }

    private function validateUrl(string $url): string {

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("URL inválida");
        }

        return $url;
    }
}
