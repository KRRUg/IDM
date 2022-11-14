<?php

namespace App\Transfer;

class Error
{
    public int $code;

    public string $message;

    public string $detail;

    public static function withMessage(string $msg)
    {
        $ret = new self();
        $ret->message = $msg;

        return $ret;
    }

    public static function withMessageAndDetail(string $msg, string $detail)
    {
        $ret = new self();
        $ret->message = $msg;
        $ret->detail = $detail;

        return $ret;
    }
}
