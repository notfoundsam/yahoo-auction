<?php

namespace Unit\Parser;

use PHPUnit\Framework\TestCase;
use Tests\Utils\FileReader;
use Yahooauc\Exceptions\ParserException;
use Yahooauc\Parser;

class InfoTest extends TestCase
{
    const TEST_DATA_DIR = __DIR__ . '/../..';

    private $fileReader;

    protected function setUp(): void
    {
        $this->fileReader = new FileReader(self::TEST_DATA_DIR);
    }

    public function testGetWonIds()
    {
        $html = $this->fileReader->readFile('closed_user.html');
        $result = Parser::getWonIds($html);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetWonIdsWrongHtml()
    {
        $html = $this->fileReader->readFile('login_url_post.html');
        $result = Parser::getWonIds($html);

        $this->assertNull($result);
    }

    public function testGetBiddingLots()
    {
        $html = $this->fileReader->readFile('open_user.html');
        $result = Parser::getBiddingLots($html);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetBiddingLotsWrongHtml()
    {
        $html = $this->fileReader->readFile('login_url_post.html');
        $result = Parser::getBiddingLots($html);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionTitle()
    {
        $html = $this->fileReader->readFile('auction_ended.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionTitle($dom);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionTitleFailed()
    {
        $html = $this->fileReader->readFile('bid_info.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionTitle($dom);

        $this->assertIsString($result);
        $this->assertEmpty($result);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionSellerId()
    {
        $html = $this->fileReader->readFile('auction_ended.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionSellerId($dom);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionSellerIdFailed()
    {
        $html = $this->fileReader->readFile('bid_info.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionSellerId($dom);

        $this->assertIsString($result);
        $this->assertEmpty($result);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionImagesUrl()
    {
        $html = $this->fileReader->readFile('auction_ended.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionImagesUrl($dom);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionImagesUrlFailed()
    {
        $html = $this->fileReader->readFile('bid_info.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionImagesUrl($dom);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionPrice()
    {
        $html = $this->fileReader->readFile('auction_ended.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionPrice($dom);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertFalse($result['price'] === 0);
        $this->assertFalse($result['taxPrice'] === 0);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionPriceFailed()
    {
        $html = $this->fileReader->readFile('bid_info.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionPrice($dom);

        $this->assertIsArray($result);
        $this->assertTrue($result['price'] === 0);
        $this->assertTrue($result['taxPrice'] === 0);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionDetail()
    {
        $html = $this->fileReader->readFile('auction_ended.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionDetail($dom);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertIsString($result['count']);
        $this->assertNotEmpty($result['count']);
        $this->assertIsString($result['start']);
        $this->assertNotEmpty($result['start']);
        $this->assertIsString($result['end']);
        $this->assertNotEmpty($result['end']);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionDetailFailed()
    {
        $html = $this->fileReader->readFile('bid_info.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionDetail($dom);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertIsString($result['count']);
        $this->assertEmpty($result['count']);
        $this->assertIsString($result['start']);
        $this->assertEmpty($result['start']);
        $this->assertIsString($result['end']);
        $this->assertEmpty($result['end']);
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionStatusEnded()
    {
        $html = $this->fileReader->readFile('auction_ended.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionStatus($dom);

        $this->assertIsString($result);
        $this->assertTrue($result === 'ended');
    }

    /**
     * @throws ParserException
     */
    public function testGetAuctionStatusOpen()
    {
        $html = $this->fileReader->readFile('bid_info.html');
        $dom = Parser::getHtmlDom($html);
        $result = Parser::getAuctionStatus($dom);

        $this->assertIsString($result);
        $this->assertTrue($result === 'open');
    }
}
