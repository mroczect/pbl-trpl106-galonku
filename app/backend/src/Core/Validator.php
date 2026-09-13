<?php
namespace App\Core;

class Validator
{
    public static function make(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleset) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $ruleset) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($name) {
                    case 'required':
                        if ($value === null || $value === '') $errors[$field][] = "$field wajib diisi";
                        break;
                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) $errors[$field][] = "$field harus email valid";
                        break;
                    case 'min':
                        if (is_string($value) && strlen($value) < (int) $param) $errors[$field][] = "$field minimal $param karakter";
                        if (is_numeric($value) && $value < (int) $param) $errors[$field][] = "$field minimal $param";
                        break;
                    case 'max':
                        if (is_string($value) && strlen($value) > (int) $param) $errors[$field][] = "$field maksimal $param karakter";
                        break;
                    case 'numeric':
                        if ($value !== null && !is_numeric($value)) $errors[$field][] = "$field harus berupa angka";
                        break;
                    case 'in':
                        $allowed = explode(',', $param ?? '');
                        if ($value !== null && !in_array($value, $allowed, true)) $errors[$field][] = "$field harus salah satu dari: $param";
                        break;
                }
            }
        }

        if (!empty($errors)) {
            Response::error('Validasi gagal', 422, $errors);
        }
        return $data;
    }
}
