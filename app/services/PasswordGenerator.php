<?php

class PasswordOptions {
    private int    $length        = 16;
    private bool   $upper         = true;
    private bool   $lower         = true;
    private bool   $digits        = true;
    private bool   $symbols       = false;
    private bool   $avoidAmbiguous = true;
    private string $exclude       = '';
    private bool   $requireEach   = true;

    public function setLength(int $length): static {
        if ($length < 4 || $length > 128) {
            throw new InvalidArgumentException("La longitud debe estar entre 4 y 128 caracteres.");
        }
        $this->length = $length;
        return $this;
    }

    public function setUpper(bool $v): static   { $this->upper = $v; return $this; }
    public function setLower(bool $v): static   { $this->lower = $v; return $this; }
    public function setDigits(bool $v): static  { $this->digits = $v; return $this; }
    public function setSymbols(bool $v): static { $this->symbols = $v; return $this; }
    public function setAvoidAmbiguous(bool $v): static { $this->avoidAmbiguous = $v; return $this; }
    public function setExclude(string $v): static { $this->exclude = $v; return $this; }
    public function setRequireEach(bool $v): static { $this->requireEach = $v; return $this; }

    public function toArray(): array {
        return [
            'length'          => $this->length,
            'upper'           => $this->upper,
            'lower'           => $this->lower,
            'digits'          => $this->digits,
            'symbols'         => $this->symbols,
            'avoid_ambiguous' => $this->avoidAmbiguous,
            'exclude'         => $this->exclude,
            'require_each'    => $this->requireEach,
        ];
    }
}

class PasswordGenerator {

    private static ?PasswordGenerator $instance = null;

    // Singleton
    public static function getInstance(): static {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    private function __construct() {}

    // Helpers internos 
    private function secureRandInt(int $min, int $max): int {
        return random_int($min, $max);
    }

    private function shuffleSecure(string $str): string {
        $arr = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        $n = count($arr);
        for ($i = $n - 1; $i > 0; $i--) {
            $j = $this->secureRandInt(0, $i);
            [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        }
        return implode('', $arr);
    }

    //Generación 
    public function generate(PasswordOptions $options): string {
        $opts = $options->toArray();
        $length = $opts['length'];

        $charSets = [];
        if ($opts['upper'])   $charSets['upper']   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($opts['lower'])   $charSets['lower']   = 'abcdefghijklmnopqrstuvwxyz';
        if ($opts['digits'])  $charSets['digits']  = '0123456789';
        if ($opts['symbols']) $charSets['symbols'] = '!@#$%^&*()-_=+[]{}|;:,.<>?';

        if (empty($charSets)) {
            throw new InvalidArgumentException("Debe activarse al menos una categoría de caracteres.");
        }

        $excludeChars = $opts['exclude'];
        if ($opts['avoid_ambiguous']) {
            $excludeChars .= 'Il1O0o';
        }
        $excludeMap = array_flip(
            array_unique(preg_split('//u', $excludeChars, -1, PREG_SPLIT_NO_EMPTY))
        );

            foreach ($charSets as $k => $chars) {
            $filtered = implode('', array_filter(
                preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY),
                fn($c) => !isset($excludeMap[$c])
            ));
            if ($filtered === '') {
                throw new InvalidArgumentException("La categoría '{$k}' queda vacía tras las exclusiones.");
            }
            $charSets[$k] = $filtered;
        }

        $pool = implode('', array_values($charSets));
        $passwordChars = [];

        if ($opts['require_each']) {
            foreach ($charSets as $chars) {
                $passwordChars[] = $chars[$this->secureRandInt(0, strlen($chars) - 1)];
            }
        }

        $needed = $length - count($passwordChars);
        for ($i = 0; $i < $needed; $i++) {
            $passwordChars[] = $pool[$this->secureRandInt(0, strlen($pool) - 1)];
        }

        return $this->shuffleSecure(implode('', $passwordChars));
    }

    public function generateMultiple(int $count, PasswordOptions $options): array {
        if ($count < 1 || $count > 100) {
            throw new InvalidArgumentException("El count debe estar entre 1 y 100.");
        }
        $passwords = [];
        for ($i = 0; $i < $count; $i++) {
            $passwords[] = $this->generate($options);
        }
        return $passwords;
    }
}