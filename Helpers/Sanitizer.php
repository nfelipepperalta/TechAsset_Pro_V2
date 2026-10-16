<?php
// ============================================================
//  helpers/Sanitizer.php
//  Limpieza y normalización de inputs del usuario
// ============================================================

namespace Helpers;

class Sanitizer
{
    // Limpiar string básico
    public static function string(?string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value ?? '')));
    }

    // Limpiar email
    public static function email(?string $value): string
    {
        return strtolower(trim(filter_var($value ?? '', FILTER_SANITIZE_EMAIL)));
    }

    // Limpiar entero
    public static function int(mixed $value): ?int
    {
        $filtered = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        return $filtered !== false && $filtered !== '' ? (int)$filtered : null;
    }

    // Limpiar decimal
    public static function float(mixed $value): ?float
    {
        $filtered = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        return $filtered !== false && $filtered !== '' ? (float)$filtered : null;
    }

    // Limpiar fecha — retorna null si no es válida
    public static function date(?string $value): ?string
    {
        $value = trim($value ?? '');
        if (empty($value)) return null;
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    // Limpiar array completo de POST
    public static function post(array $data, array $fields): array
    {
        $clean = [];
        foreach ($fields as $field => $type) {
            $value = $data[$field] ?? null;
            $clean[$field] = match($type) {
                'int'   => self::int($value),
                'float' => self::float($value),
                'email' => self::email($value),
                'date'  => self::date($value),
                default => self::string($value),
            };
        }
        return $clean;
    }
}
