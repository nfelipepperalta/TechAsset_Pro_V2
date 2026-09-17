<?php
// ============================================================
//  helpers/Validator.php
//  Validación de datos de formularios
// ============================================================

namespace Helpers;

class Validator
{
    private array $errors = [];
    private array $data   = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // Campo requerido
    public function required(string $field, string $label): self
    {
        if (empty(trim($this->data[$field] ?? ''))) {
            $this->errors[$field] = "{$label} es requerido.";
        }
        return $this;
    }

    // Longitud mínima
    public function minLength(string $field, int $min, string $label): self
    {
        $value = $this->data[$field] ?? '';
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field] = "{$label} debe tener al menos {$min} caracteres.";
        }
        return $this;
    }

    // Email válido
    public function email(string $field, string $label = 'Email'): self
    {
        $value = $this->data[$field] ?? '';
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} no tiene un formato válido.";
        }
        return $this;
    }

    // Valor dentro de una lista permitida
    public function inList(string $field, array $allowed, string $label): self
    {
        $value = $this->data[$field] ?? '';
        if (!empty($value) && !in_array($value, $allowed)) {
            $this->errors[$field] = "{$label} no tiene un valor válido.";
        }
        return $this;
    }

    // Fecha válida
    public function date(string $field, string $label): self
    {
        $value = $this->data[$field] ?? '';
        if (!empty($value) && !strtotime($value)) {
            $this->errors[$field] = "{$label} no tiene un formato de fecha válido.";
        }
        return $this;
    }

    // Numérico positivo
    public function numeric(string $field, string $label): self
    {
        $value = $this->data[$field] ?? '';
        if (!empty($value) && (!is_numeric($value) || $value < 0)) {
            $this->errors[$field] = "{$label} debe ser un número positivo.";
        }
        return $this;
    }

    // ¿Hay errores?
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    // Obtener todos los errores
    public function errors(): array
    {
        return $this->errors;
    }

    // Obtener valor limpio
    public function get(string $field, mixed $default = null): mixed
    {
        return $this->data[$field] ?? $default;
    }
}
