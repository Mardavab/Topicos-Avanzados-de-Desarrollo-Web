<?php

/**
 * PasswordController — maneja los 3 endpoints de la API.
 *
 * Endpoints:
 *   GET  /api/password          → generar 1 contraseña
 *   POST /api/passwords         → generar múltiples contraseñas
 *   POST /api/password/validate → validar fortaleza
 */
class PasswordController {

    private PasswordGenerator $generator;
    private PasswordValidator $validator;

    public function __construct() {
        $this->generator = PasswordGenerator::getInstance();
        $this->validator = new PasswordValidator();
    }

    // ── GET /api/password ────────────────────────────────────
    public function generate(): never {
        $iv = new InputValidator();

        // Los parámetros vienen por query string
        $opts = $iv->buildOptions($_GET);

        if ($iv->hasErrors()) {
            Response::error('Parámetros inválidos', 400, $iv->getErrors());
        }

        try {
            $password = $this->generator->generate($opts);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        }

        $optsArr = $opts->toArray();
        Response::success([
            'password' => $password,
            'length'   => mb_strlen($password),
            'options'  => [
                'includeUppercase'  => $optsArr['upper'],
                'includeLowercase'  => $optsArr['lower'],
                'includeNumbers'    => $optsArr['digits'],
                'includeSymbols'    => $optsArr['symbols'],
                'excludeAmbiguous'  => $optsArr['avoid_ambiguous'],
            ],
        ], 'Contraseña generada correctamente');
    }

    // ── POST /api/passwords ──────────────────────────────────
    public function generateMultiple(): never {
        $body = $this->parseJsonBody();
        $iv   = new InputValidator();

        $count = $iv->validateCount($body['count'] ?? 5);
        $opts  = $iv->buildOptions($body);

        if ($iv->hasErrors()) {
            Response::error('Parámetros inválidos', 400, $iv->getErrors());
        }

        try {
            $passwords = $this->generator->generateMultiple($count, $opts);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        }

        $optsArr = $opts->toArray();
        Response::success([
            'count'     => count($passwords),
            'length'    => $optsArr['length'],
            'passwords' => $passwords,
            'options'   => [
                'includeUppercase' => $optsArr['upper'],
                'includeLowercase' => $optsArr['lower'],
                'includeNumbers'   => $optsArr['digits'],
                'includeSymbols'   => $optsArr['symbols'],
                'excludeAmbiguous' => $optsArr['avoid_ambiguous'],
            ],
        ], "{$count} contraseñas generadas correctamente");
    }

    // ── POST /api/password/validate ──────────────────────────
    public function validatePassword(): never {
        $body = $this->parseJsonBody();
        $iv   = new InputValidator();

        $password = $iv->validatePassword($body['password'] ?? '');

        if ($iv->hasErrors()) {
            Response::error('Parámetros inválidos', 400, $iv->getErrors());
        }

        $requirements = $body['requirements'] ?? [];
        $result = $this->validator->validate($password, $requirements);

        Response::success($result, 'Validación completada');
    }

    // ── Helper: parsear JSON del body ────────────────────────
    private function parseJsonBody(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('El body debe ser JSON válido.', 400);
        }
        return $data;
    }
}