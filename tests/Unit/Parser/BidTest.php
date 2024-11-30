<?php

namespace Unit\Parser;

use PHPUnit\Framework\TestCase;
use Tests\Utils\FileReader;
use Yahooauc\Exceptions\ParserException;
use Yahooauc\Exceptions\RebidException;
use Yahooauc\Parser;

class BidTest extends TestCase
{
    const TEST_DATA_DIR = __DIR__ . '/../..';

    private $fileReader;

    protected function setUp(): void
    {
        $this->fileReader = new FileReader(self::TEST_DATA_DIR);
    }

    /**
     * @throws ParserException
     */
    public function testBidInfoGetHiddenInputs()
    {
        $html = $this->fileReader->readFile('bid_info.html');
        $result = Parser::getHiddenInputs($html);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @throws ParserException
     */
    public function testBidPreviewGetHiddenInputs()
    {
        $html = $this->fileReader->readFile('bid_preview.html');
        $result = Parser::getHiddenInputs($html);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @throws RebidException
     * @throws ParserException
     */
    public function testGetResultSuccessBid()
    {
        $html = $this->fileReader->readFile('place_bid.html');
        $result = Parser::getResult($html);

        $this->assertTrue($result);
    }

    /**
     * @throws ParserException
     */
    public function testGetResultPriceUpBid()
    {
        $this->expectException(RebidException::class);

        $html = $this->fileReader->readFile('place_bid_price_up.html');
        Parser::getResult($html);
    }

    public function testNotIsEndedBid()
    {
        $html = $this->fileReader->readFile('bid_info.html');
        $result = Parser::isEnded($html);

        $this->assertFalse($result);
    }

    public function testIsEndedBid()
    {
        $html = $this->fileReader->readFile('auction_ended.html');
        $result = Parser::isEnded($html);

        $this->assertTrue($result);
    }

    /**
     * @throws RebidException
     */
    public function testGetResultUnexpected()
    {
        $this->expectException(ParserException::class);

        $html = $this->fileReader->readFile('login_url_post.html');
        Parser::getResult($html);
    }
}
