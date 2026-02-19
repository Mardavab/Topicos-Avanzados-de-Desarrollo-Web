<?php
    
class PasswordValidator {

    public function validate(string $password, array $requirements = []): array {
        $checks  = [];
        $failed  = [];
        $score   = 0;

        $len = mb_strlen($password);

        // Longitud
        $minLen = $requirements['minLength'] ?? 8;
        $maxLen = $requirements['maxLength'] ?? 128;

        $checks['min_length'] = $len >= $minLen;
        if (!$checks['min_length']) {
            $failed[] = "Debe tener al menos {$minLen} caracteres (tiene {$len}).";
        }

        $checks['max_length'] = $len <= $maxLen;
        if (!$checks['max_length']) {
            $failed[] = "No debe superar {$maxLen} caracteres.";
        }

        // Tipos de caracteres
        $hasUpper   = (bool) preg_match('/[A-Z]/', $password);
        $hasLower   = (bool) preg_match('/[a-z]/', $password);
        $hasDigit   = (bool) preg_match('/[0-9]/', $password);
        $hasSymbol  = (bool) preg_match('/[^A-Za-z0-9]/', $password);

        if ($requirements['requireUppercase'] ?? false) {
            $checks['uppercase'] = $hasUpper;
            if (!$hasUpper) $failed[] = "Debe contener al menos una letra mayúscula.";
        }

        if ($requirements['requireLowercase'] ?? false) {
            $checks['lowercase'] = $hasLower;
            if (!$hasLower) $failed[] = "Debe contener al menos una letra minúscula.";
        }

        if ($requirements['requireNumbers'] ?? false) {
            $checks['numbers'] = $hasDigit;
            if (!$hasDigit) $failed[] = "Debe contener al menos un número.";
        }

        if ($requirements['requireSymbols'] ?? false) {
            $checks['symbols'] = $hasSymbol;
            if (!$hasSymbol) $failed[] = "Debe contener al menos un símbolo especial.";
        }

        // Puntos
        // Longitud base
        $score += min(40, $len * 2);          // hasta 40 pts por longitud
        if ($hasUpper)  $score += 15;
        if ($hasLower)  $score += 15;
        if ($hasDigit)  $score += 15;
        if ($hasSymbol) $score += 15;
        $score = min(100, $score);

        $strength = match (true) {
            $score >= 80 => 'strong',
            $score >= 60 => 'medium',
            $score >= 40 => 'weak',
            default      => 'very_weak',
        };

        //Sugerencias
        $suggestions = [];
        if ($len < 12)     $suggestions[] = "Usa al menos 12 caracteres para mayor seguridad.";
        if (!$hasUpper)    $suggestions[] = "Agrega letras mayúsculas.";
        if (!$hasLower)    $suggestions[] = "Agrega letras minúsculas.";
        if (!$hasDigit)    $suggestions[] = "Agrega números.";
        if (!$hasSymbol)   $suggestions[] = "Agrega símbolos especiales (!@#$...).";

        return [
            'valid'       => empty($failed),
            'score'       => $score,
            'strength'    => $strength,
            'checks'      => $checks,
            'errors'      => $failed,
            'suggestions' => $suggestions,
        ];
    }
}