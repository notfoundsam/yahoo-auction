<?php

namespace Yahooauc\Exceptions;

class AuctionEndedException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Auction has ended');
    }
}
