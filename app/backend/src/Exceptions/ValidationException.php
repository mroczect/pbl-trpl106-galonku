<?php
namespace App\Exceptions;

class ValidationException extends \Exception
{
    protected array $errors;

    public function __construct(string $message = 'Validasi gagal', array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
