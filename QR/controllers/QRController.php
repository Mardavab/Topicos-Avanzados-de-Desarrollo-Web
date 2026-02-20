<?php

require_once __DIR__ . '/../services/QRService.php';

class QRController {

    private $service;

    public function __construct() {
        $this->service = new QRService();
    }

    public function generate() {

        try {

            $data = json_decode(file_get_contents("php://input"), true);

            $type = $data['type'] ?? 'text';
            $size = $data['size'] ?? 300;
            $errorCorrection = $data['errorCorrection'] ?? 'M';

            switch ($type) {

                case 'wifi':
                    $content = $this->service->buildWifi(
                        $data['ssid'],
                        $data['password'],
                        $data['encryption']
                    );
                    break;

                case 'geo':
                    $content = $this->service->buildGeo(
                        $data['lat'],
                        $data['lng']
                    );
                    break;

                case 'url':
                    if (!filter_var($data['content'], FILTER_VALIDATE_URL)) {
                        throw new Exception("URL inválida");
                    }
                    $content = $data['content'];
                    break;

                default:
                    $content = $data['content'];
            }

            $qr = $this->service->generate($content, $size, $errorCorrection);

            header("Content-Type: image/png");
            echo $qr;

        } catch (Exception $e) {

            http_response_code(400);
            echo json_encode([
                "error" => $e->getMessage()
            ]);
        }
    }
}