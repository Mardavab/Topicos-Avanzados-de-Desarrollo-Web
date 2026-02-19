<?php

class InputValidator {

    private array $errors = [];

    public function getErrors(): array {
        return $this->errors;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    //Validaciones individuales

    public function validateLength(mixed $value, string $field = 'length'): int {
        $v = filter_var($value, FILTER_VALIDATE_INT);
        if ($v === false) {
            $this->errors[] = "'{$field}' debe ser un entero.";
            return 16;
        }
        if ($v < 4 || $v > 128) {
            $this->errors[] = "'{$field}' debe estar entre 4 y 128. Se recibió: {$v}.";
        }
        return (int) $v;
    }

    public function validateCount(mixed $value): int {
        $v = filter_var($value, FILTER_VALIDATE_INT);
        if ($v === false || $v < 1 || $v > 100) {
            $this->errors[] = "'count' debe ser un entero entre 1 y 100.";
            return 1;
        }
        return (int) $v;
    }

    public function validateBool(mixed $value, string $field): bool {
        if (is_bool($value)) return $value;
        if (in_array($value, ['true', '1', 1, true], true))  return true;
        if (in_array($value, ['false', '0', 0, false], true)) return false;
        $this->errors[] = "'{$field}' debe ser booleano (true/false).";
        return false;
    }

    public function validatePassword(mixed $value): string {
        if (!is_string($value) || trim($value) === '') {
            $this->errors[] = "'password' es requerido y debe ser una cadena de texto.";
            return '';
        }
        if (mb_strlen($value) > 512) {
            $this->errors[] = "'password' no puede superar 512 caracteres.";
        }
        return $value;
    }

    //Construye PasswordOptions desde un array de input 
    public function buildOptions(array $input): PasswordOptions {
        $opts = new PasswordOptions();

        $length = $this->validateLength($input['length'] ?? 16);
        if (!$this->hasErrors()) {
            $opts->setLength($length);
        }

        $opts->setUpper($this->validateBool($input['includeUppercase'] ?? true,  'includeUppercase'));
        $opts->setLower($this->validateBool($input['includeLowercase'] ?? true,  'includeLowercase'));
        $opts->setDigits($this->validateBool($input['includeNumbers']  ?? true,  'includeNumbers'));
        $opts->setSymbols($this->validateBool($input['includeSymbols'] ?? false, 'includeSymbols'));
        $opts->setAvoidAmbiguous($this->validateBool($input['excludeAmbiguous'] ?? true, 'excludeAmbiguous'));

        if (isset($input['excludeChars'])) {
            $opts->setExclude((string) $input['excludeChars']);
        }

        return $opts;
    }
}