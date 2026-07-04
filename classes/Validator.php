<?php

class Validator
{
    public static function required(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[$field] = 'این فیلد الزامی است.';
            }
        }
        return $errors;
    }

    public static function sanitizeString($value): string
    {
        return trim(filter_var((string)$value, FILTER_UNSAFE_RAW));
    }

    public static function validateInt($value, string $field, int $min = 1): array
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min) {
            return [$field => 'مقدار نامعتبر است.'];
        }
        return [];
    }

    public static function validateFloat($value, string $field, float $min = 0.01): array
    {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < $min) {
            return [$field => 'مقدار باید عددی و بزرگتر از صفر باشد.'];
        }
        return [];
    }

    public static function validateEnum($value, string $field, array $options): array
    {
        if (!in_array($value, $options, true)) {
            return [$field => 'مقدار انتخاب شده نامعتبر است.'];
        }
        return [];
    }

    public static function validateJsonString($value, string $field): array
    {
        if ($value === '' || $value === null) {
            return [];
        }

        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [$field => 'JSON نامعتبر است.'];
        }

        return [];
    }

    public static function validateAmount($value, string $field): array
    {
        if (!is_numeric($value) || (float)$value <= 0) {
            return [$field => 'مبلغ باید عددی و بیشتر از صفر باشد.'];
        }
        return [];
    }
}
