<?php

namespace Integration;

use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Yahooauc\Browser;
use Yahooauc\Exceptions\ApiException;
use Yahooauc\Exceptions\LoggedOffException;
use Yahooauc\Exceptions\PageNotfoundException;

class InfoTest extends TestCase
{
    /**
     * @throws GuzzleException
     * @throws ApiException
     */
    public function testGetAuctionInfoAsXml()
    {
        $browser = new Browser('test_user', 'secret_password', null, []);
        $result = $browser->getAuctionInfoAsXml('e000000000');

        $this->assertIsObject($result);
        $this->assertObjectHasAttribute('Result', $result);
    }

    /**
     * @throws ApiException
     * @throws GuzzleException
     */
    public function testGetAuctionInfoAsXmlNotFound()
    {
        $this->expectException(PageNotfoundException::class);

        $browser = new Browser('test_user', 'secret_password', null, []);
        $browser->getAuctionInfoAsXml('n000000000');
    }

    /**
     * @throws GuzzleException
     * @throws LoggedOffException
     */
    public function testGetBiddingLots()
    {
        $browser = new Browser('test_user', 'secret_password', null, []);
        $result = $browser->getBiddingLots();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @throws GuzzleException
     * @throws LoggedOffException
     */
    public function testGetWonIds()
    {
        $browser = new Browser('test_user', 'secret_password', null, []);
        $result = $browser->getWonIds();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetBiddingLotsLogout()
    {
        $this->expectException(LoggedOffException::class);

        $browser = new Browser('test_user2', 'secret_password', null, []);
        $browser->getBiddingLots();
    }

    /**
     * @throws GuzzleException
     */
    public function testGetWonIdsLogout()
    {
        $this->expectException(LoggedOffException::class);

        $browser = new Browser('test_user2', 'secret_password', null, []);
        $browser->getWonIds();
    }
}
