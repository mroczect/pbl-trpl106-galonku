<?php
namespace App\Testing;

class ResponseCaptured extends \Exception
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = []
    ) {
        parent::__construct("Response captured with status $status");
    }
}
