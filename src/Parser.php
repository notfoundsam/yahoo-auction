<?php

namespace Yahooauc;

use Yahooauc\Exceptions\ParserException;
use Yahooauc\Exceptions\RebidException;
use KubAT\PhpSimple\HtmlDomParser;

/**
 * Class parse Yahoo auction HTML pages
 *
 * @category Parser
 * @package  Yahoo\Auction
 * @author   Zazimko Alexey <notfoundsam@gmail.com>
 * @link     https://github.com/notfoundsam/yahoo-auction
 *
 */
class Parser
{
    private static $JP          = array(",", "円", "分", "時間", "日");
    private static $EN          = array("", "", "min", "hour", "day");
    private static $BID_SUCCESS = '入札を受け付けました';
    private static $PRICE_UP    = '/再入札/';
    private static $AUCTION_WON = 'あなたが落札しました';
    private static $AUCTION_ENDED = 'このオークションは終了しています';
    private static $TABLE_WON   = 8;
    private static $TABLE_BID   = 8;

    /**
     * Check correct Login
     *
     * @param  string $body     HTML of login page
     * @param  string $userName Login user name
     * @return bool             Return true if login is correct or false is fail
     *
     */
    public static function checkLogin(&$body, &$userName)
    {
        preg_match('/ようこそ、.+('.$userName.').+さん/', $body, $matches);

        return isset($matches[1]) && $matches[1] === $userName;
    }

    /**
     * Get only Auction IDs from Auction won page
     *
     * @param  string $body HTML page with won auctions
     * @return array        Array of auction ids
     *
     */
    public static function getWonIds(&$body)
    {
        $html = static::getHtmlDom($body);

        $ids = [];

        $won_table = self::findTable($html, self::$TABLE_WON);

        if (!$won_table)
        {
            return null;
        }
        
        $first_tr = true;

        foreach ($won_table->children as $key => $tr)
        {
            if (!$tr->children()) continue;

            if ($first_tr) {
                $first_tr = false;
                continue;
            }

            $a_td = $tr->children();
            $ids[] = strip_tags($a_td[1]->innertext);
        }

        return $ids;
    }

    /**
     * Get all values (like ID, Title, Price etc.) from bidding page
     *
     * @param  string $body HTML page with bidding auctions
     * @return array  Return array with auctions in bid
     *
     */
    public static function getBiddingLots(&$body)
    {
        $html = static::getHtmlDom($body);

        $lots = [];

        $bidding_table = self::findTable($html, self::$TABLE_BID);

        if (!$bidding_table)
        {
            return $lots;
        }

        $is_header = true;

        foreach ($bidding_table->children as $i => $tr)
        {
            $lot = [];

            if ( !$tr->children())
                continue;

            if ($is_header)
            {
                $is_header = false;
                continue;
            }

            foreach ($tr->children() as $j => $td)
            {
                if ($j > 6)
                    break;

                switch ($j)
                {
                    case 0:
                        break;
                    case 1:
                        $tmp = explode('/', $td->find('a', 0)->href);
                        $lot['id'] = end($tmp);
                        $lot['title'] = trim(strip_tags($td->innertext));
                        break;
                    case 2:
                        $lot['price'] = trim(str_replace(static::$JP, static::$EN, strip_tags($td->innertext)));
                        break;
                    case 3:
                        $lot['bids'] = trim(strip_tags($td->innertext));
                        break;
                    case 4:
                        $lot['vendor'] = trim(strip_tags($td->innertext));
                        break;
                    case 5:
                        $lot['bidder'] = trim(strip_tags($td->innertext));
                        break;
                    case 6:
                        $lot['end'] = trim(str_replace(static::$JP, static::$EN, strip_tags($td->innertext)));
                        break;
                    default:
                        break;
                }
            }
            
            $lots[] = $lot;
        }
        
        return $lots;
    }

    /**
     * Parse HTML page for hidden fields in form
     *
     * @param  string $body    Html page with form
     * @return array           Return array with pair name and value
     * @throws ParserException Throw exception if POST form not found
     *
     */
    public static function getHiddenInputs(&$body)
    {
        $html = static::getHtmlDom($body);

        $arr = [];

        if ($form = $html->find('form[method=post]', 0))
        {
            $inputs = $form->find('input[type=hidden]');

            foreach ($inputs as $input)
            {
                $arr[] = ['name' => $input->name, 'value' => $input->value];
            }
        }
        else
        {
            throw new ParserException('Page POST form not found', 20);
        }

        return $arr;
    }

    /**
     * Check result of bid.
     *
     * @param  string $body    Html page with bid result
     * @return bool            Return true if bid was success or false if unknown result (like won lots limit etc.)
     *
     * @throws RebidException  Throw exception if price of bid under then current price
     * @throws ParserException Throw exception if bid was not success
     *
     */
    public static function getResult(&$body)
    {
        $bidSucces = strpos($body, static::$BID_SUCCESS);

        if ($bidSucces !== false) return true;

        $actionWon = strpos($body, static::$AUCTION_WON);

        if ($actionWon !== false) return true;

        if (preg_match(static::$PRICE_UP, $body)) {
            throw new RebidException;
        }

        throw new ParserException('Page says: '.$body, 50);
    }

    /**
     * Check the auction closing
     *
     * @param $body
     * @return bool
     */
    public static function isEnded(&$body)
    {
        $isEnded = strpos($body, static::$AUCTION_ENDED);

        return ($isEnded !== false);
    }

    /**
     * @param $body
     * @return mixed
     */
    public static function getCaptchaId(&$body)
    {
        $html = static::getHtmlDom($body);

        if ($id = $html->find('#captchaId', 0)) {
            return $id->value;
        }

        return null;
    }

    /**
     * @param $html
     * @return string
     */
    public static function getAuctionTitle($html)
    {
        $val = $html->find('.ProductTitle__text', 0);

        return $val ? $val->innertext : '';
    }

    /**
     * @param $html
     * @return string
     */
    public static function getAuctionSellerId($html)
    {
        $val = $html->find('.Seller__name a', 0);

        return $val ? $val->innertext : '';
    }

    /**
     * @param $html
     * @return array
     */
    public static function getAuctionImagesUrl($html)
    {
        $urls = [];

        if ($items = $html->find('.ProductImage__images img')) {
            foreach ($items as $item) {
                $urls[] = $item->src;
            }
        }

        return $urls;
    }

    /**
     * @param $html
     * @return array
     */
    public static function getAuctionPrice($html)
    {
        $data = [
            'price' => 0,
            'taxPrice' => 0,
        ];

        $priceValue = $html->find('.Price__value', 0);

        if ($priceValue) {
            $text = str_replace(',', '', $priceValue->innertext);
            preg_match_all('/([0-9]{1,}).?円/', $text, $matches);

            if (isset($matches[1])) {
                $data['price'] = (int) $matches[1][0];

                if (isset($matches[1][1])) {
                    $data['taxPrice'] = (int) $matches[1][1];
                }
            }
        }

        return $data;
    }

    /**
     * @param $html
     * @return array
     */
    public static function getAuctionDetail($html)
    {
        $data = [
            'count' => '',
            'start' => '',
            'end' => '',
        ];

        if ($rows = $html->find('.ProductDetail__item')) {
            foreach ($rows as $row) {
                $dt = $row->find('dt', 0);
                $dd = $row->find('dd', 0);
                $timePattern = '/([0-9]{4})\.([0-9]{2})\.([0-9]{2}).+([0-9]{2}:[0-9]{2})/';

                if ($dt && $dd) {
                    $title = $dt->innertext;
                    $value = trim(str_replace('<span class="ProductDetail__bullet">：</span>', '', $dd->innertext));

                    if ($title == '個数') {
                        $data['count'] = $value;
                    }
                    else if ($title == '開始日時') {
                        if (preg_match($timePattern, $value , $matches)) {
                            $data['start'] = $matches[1].'-'.$matches[2].'-'.$matches[3].'T'.$matches[4].':00+09:00';
                        }
                    }
                    else if ($title == '終了日時') {
                        if (preg_match($timePattern, $value , $matches)) {
                            $data['end'] = $matches[1].'-'.$matches[2].'-'.$matches[3].'T'.$matches[4].':00+09:00';
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param $html
     * @return string
     */
    public static function getAuctionStatus($html)
    {
        $status = 'open';

        $val = $html->find('#closedHeader', 0);

        if ($val) {
            $innerText = $val->innertext;

            if (self::isEnded($innerText)) {
                $status = 'ended';
            }
        }

        return $status;
    }

    /**
     * * Create DOM tree from HTML
     *
     * @param string $body
     * @return false|mixed
     * @throws ParserException Throw exception if HTML is empty
     */
    public static function getHtmlDom(&$body)
    {
        if (!$body) {
            throw new ParserException('Body of HTML Document is empty', 10);
        }

        return HtmlDomParser::str_get_html($body);
    }
    
    /**
     * Find table with auctions
     *
     * @param  string $html Html with table of auctions
     * @param  string $col  Find table with $col count
     * @return object|null  Return HtmlDomParser element with table of auctions or null if not found
     *
     */
    private static function findTable(&$html = null, $col = null)
    {
        if ($tables = $html->find('table'))
        {
            foreach ($tables as $table)
            {
                if ( $t_children = $table->children() )
                {
                    foreach ($t_children as $t_child)
                    {
                        if ( $t_child->tag = 'tbody')
                        {
                            if ( $t_body_children = $t_child->children() )
                            {
                                if ( count($t_body_children) == $col )
                                {
                                    return $table;
                                }
                            }
                        }
                    }
                }
            }
        }

        return null;
    }
}
