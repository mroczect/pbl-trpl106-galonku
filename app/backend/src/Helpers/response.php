<?php
if (!function_exists('success_response')) {
    function success_response(mixed $data = null, string $message = 'OK'): void {
        \App\Core\Response::success($data, $message);
    }
}

if (!function_exists('error_response')) {
    function error_response(string $message, int $status = 400): void {
        \App\Core\Response::error($message, $status);
    }
}
