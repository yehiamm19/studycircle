<?php

declare(strict_types=1);

namespace App\Utils;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $ruleSet) as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }
        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, array $data): void
    {
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        match ($rule) {
            'required' => $this->required($field, $value),
            'email' => $this->email($field, $value),
            'min' => $this->min($field, $value, (int) ($params[0] ?? 0)),
            'max' => $this->max($field, $value, (int) ($params[0] ?? 0)),
            'confirmed' => $this->confirmed($field, $value, $data[$field . '_confirmation'] ?? null),
            'in' => $this->in($field, $value, $params),
            default => null,
        };
    }

    private function required(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    private function email(string $field, mixed $value): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Please enter a valid email address.';
        }
    }

    private function min(string $field, mixed $value, int $min): void
    {
        if ($value !== null && strlen((string) $value) < $min) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.";
        }
    }

    private function max(string $field, mixed $value, int $max): void
    {
        if ($value !== null && strlen((string) $value) > $max) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$max} characters.";
        }
    }

    private function confirmed(string $field, mixed $value, mixed $confirm): void
    {
        if ($value !== $confirm) {
            $this->errors[$field] = 'Passwords do not match.';
        }
    }

    private function in(string $field, mixed $value, array $allowed): void
    {
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->errors[$field] = 'Invalid selection.';
        }
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): ?string
    {
        return reset($this->errors) ?: null;
    }
}

