<?php
namespace App\Exceptions;

class NotFoundException extends \Exception
{
    public function __construct(string $message = 'Data tidak ditemukan', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
