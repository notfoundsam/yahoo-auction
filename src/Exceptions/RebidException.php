<?php

namespace Yahooauc\Exceptions;

class RebidException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Rebid page. Try with a highest price');
    }
}
