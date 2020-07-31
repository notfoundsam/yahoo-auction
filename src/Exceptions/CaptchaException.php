<?php

namespace Yahooauc\Exceptions;

class CaptchaException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Captcha is required');
    }
}
