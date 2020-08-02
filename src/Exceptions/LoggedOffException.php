<?php

namespace Yahooauc\Exceptions;

class LoggedOffException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Logged off');
    }
}
