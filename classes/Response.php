<?php namespace OnePilot;

class Response
{
    public static function make($data, $status = 200)
    {
        http_response_code($status);

        exit(json_encode($data));
    }
}