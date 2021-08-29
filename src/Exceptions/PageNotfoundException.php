<?php

namespace Yahooauc\Exceptions;

class PageNotfoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Page not found');
    }
}
