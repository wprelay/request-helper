<?php

namespace Cartrabbit\Request;

class Response
{
    public static function success($data = [], $status = 200)
    {
        return wp_send_json_success($data, $status);
    }


    public static function error($data = [], $status = 500)
    {
        return wp_send_json_error($data, $status);
    }
}