<?php

namespace Integration;

use PHPUnit\Framework\TestCase;
use Yahooauc\Browser;
use Yahooauc\Exceptions\ApiException;
use Yahooauc\Exceptions\AuctionEndedException;
use Yahooauc\Exceptions\BrowserException;
use Yahooauc\Exceptions\RebidException;

class BidTest extends TestCase
{
    /**
     * @throws AuctionEndedException
     * @throws BrowserException
     * @throws ApiException
     * @throws RebidException
     */
    public function testBid()
    {
        $browser = new Browser('test_user', 'secret_password', null, []);
        $result = $browser->bid('x000000000', 1000);

        $this->assertTrue($result);
    }

    /**
     * @throws ApiException
     * @throws RebidException
     * @throws AuctionEndedException
     */
    public function testBidLowPrice()
    {
        $this->expectException(BrowserException::class);

        $browser = new Browser('test_user', 'secret_password', null, []);
        $browser->bid('x000000000', 100);
    }

    /**
     * @throws ApiException
     * @throws AuctionEndedException
     * @throws BrowserException
     */
    public function testBidNotEnoughPrice()
    {
        $this->expectException(RebidException::class);

        $browser = new Browser('test_user', 'secret_password', null, []);
        $browser->bid('x000000000', 500);
    }

    /**
     * @throws ApiException
     * @throws RebidException
     * @throws BrowserException
     */
    public function testBidFinished()
    {
        $this->expectException(AuctionEndedException::class);

        $browser = new Browser('test_user', 'secret_password', null, []);
        $browser->bid('e000000000', 1000);
    }
}
