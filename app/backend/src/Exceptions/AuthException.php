<?php
namespace App\Exceptions;

class AuthException extends \Exception
{
    public function __construct(string $message = 'Tidak terautentikasi', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
