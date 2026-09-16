<?php
namespace App\Core;

use App\Exceptions\ValidationException;

class Validator
{
    public static function make(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleset) {
            $value = $data[$field] ?? null;
            $ruleList = is_array($ruleset) ? $ruleset : explode('|', $ruleset);

            foreach ($ruleList as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($name) {
                    case 'required':
                        if ($value === null || $value === '') $errors[$field][] = "$field wajib diisi";
                        break;
                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL))
                            $errors[$field][] = "$field harus email valid";
                        break;
                    case 'min':
                        if (is_string($value) && mb_strlen($value) < (int)$param)
                            $errors[$field][] = "$field minimal $param karakter";
                        if (is_numeric($value) && $value < (int)$param)
                            $errors[$field][] = "$field minimal $param";
                        break;
                    case 'max':
                        if (is_string($value) && mb_strlen($value) > (int)$param)
                            $errors[$field][] = "$field maksimal $param karakter";
                        if (is_numeric($value) && $value > (int)$param)
                            $errors[$field][] = "$field maksimal $param";
                        break;
                    case 'numeric':
                        if ($value !== null && !is_numeric($value))
                            $errors[$field][] = "$field harus berupa angka";
                        break;
                    case 'integer':
                        if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false)
                            $errors[$field][] = "$field harus bilangan bulat";
                        break;
                    case 'in':
                        $allowed = explode(',', $param ?? '');
                        if ($value !== null && !in_array((string)$value, $allowed, true))
                            $errors[$field][] = "$field harus salah satu dari: $param";
                        break;
                    case 'array':
                        if ($value !== null && !is_array($value))
                            $errors[$field][] = "$field harus berupa array";
                        break;
                    case 'date':
                        if ($value !== null && strtotime((string)$value) === false)
                            $errors[$field][] = "$field harus tanggal valid";
                        break;
                    case 'boolean':
                        if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1'], true))
                            $errors[$field][] = "$field harus boolean";
                        break;
                    case 'phone':
                        if ($value !== null && !preg_match('/^[0-9+\-\s]{8,20}$/', (string)$value))
                            $errors[$field][] = "$field format nomor tidak valid";
                        break;
                }
            }

            if (!isset($errors[$field]) && $value !== null) {
                $validated[$field] = $value;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Validasi gagal', $errors);
        }

        return $validated;
    }
}
