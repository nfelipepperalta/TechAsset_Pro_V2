<?php
// ============================================================
//  helpers/DateHelper.php
//  Utilidades de fechas y tiempos
// ============================================================

namespace Helpers;

class DateHelper
{
    // Días restantes desde hoy hasta una fecha
    public static function daysUntil(?string $date): ?int
    {
        if (!$date) return null;
        $diff = (int)((strtotime($date) - strtotime('today')) / 86400);
        return $diff;
    }

    // Formatear fecha a formato legible en español
    public static function format(?string $date, string $format = 'd/m/Y'): string
    {
        if (!$date) return '—';
        return date($format, strtotime($date));
    }

    // Retorna true si la fecha ya venció
    public static function isExpired(?string $date): bool
    {
        if (!$date) return false;
        return strtotime($date) < strtotime('today');
    }

    // Retorna true si la fecha vence en N días o menos
    public static function expiresSoon(?string $date, int $days = 30): bool
    {
        if (!$date) return false;
        $remaining = self::daysUntil($date);
        return $remaining !== null && $remaining >= 0 && $remaining <= $days;
    }

    // Tiempo relativo — "hace 2 días", "en 5 días"
    public static function relative(?string $date): string
    {
        if (!$date) return '—';
        $diff = self::daysUntil($date);
        if ($diff === null) return '—';
        if ($diff === 0)    return 'hoy';
        if ($diff === 1)    return 'mañana';
        if ($diff === -1)   return 'ayer';
        if ($diff > 1)      return "en {$diff} días";
        return "hace " . abs($diff) . " días";
    }
}
