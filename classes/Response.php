<?php namespace OnePilot;

class Response
{
    public static function make($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');

        exit(json_encode(['status' => $status] + $data));
    }
}
