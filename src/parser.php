<?php

namespace Yahoo\Auction;

use Yahoo\Auction\Exceptions\ParserException;
use Sunra\PhpSimple\HtmlDomParser;

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
    private static $BID_SUCCESS = '入札を受け付けました。あなたが現在の最高額入札者です。';
    private static $PRICE_UP    = '/再入札/';
    private static $AUCTION_WON = 'おめでとうございます!!　あなたが落札しました。';
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
        $html = static::getHtmlDom($body);

        if ($p_result = $html->find('div[class=yjmthloginarea]', 0))
        {
            if ($userName == trim($p_result->find('strong', 0)->innertext))
            {
                return true;
            }
        }

        return false;
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
            $a_tr = [];
            if (!$tr->children())
                continue;

            if ($first_tr){
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
     * Also get all links to another bidding pages (exclude current page) if they exist
     *
     * @param  string $body HTML page with bidding auctions
     * @return array|null   Return array with auctions in bid and array of pages ['auctions', 'pages'] or null if list of auctions is empty
     *
     */
    public static function getBiddingLots(&$body)
    {
        $html = static::getHtmlDom($body);

        $data = [];
        $a_pages = [];

        $bidding_table = self::findTable($html, self::$TABLE_BID);

        if (!$bidding_table)
        {
            return null;
        }

        if ($p_t1 = $html->find('table', 3))
        {
            if ($p_t2 = $p_t1->find('table', 3))
            {
                if ($p_td = $p_t2->find('td', 0))
                {
                    $pages = $p_td->find('a');
                    foreach($pages as $page)
                    {
                        if ( !(int)$page->innertext )
                            break;
                        
                        $a_pages[] = $page->innertext;
                    }
                }
            }   
        }

        $data['pages'] = $a_pages;

        $a_auctions = [];
        $first_tr = true;

        foreach ($bidding_table->children as $key => $tr)
        {
            $a_tr = [];
            if (!$tr->children())
                continue;

            if ($first_tr)
            {
                $first_tr = false;
                continue;
            }

            foreach ($tr->children() as $i => $td)
            {
                if ($i == 0)
                {
                    continue;
                }
                if ($i > 6)
                    break;
                if ($i == 1)
                {
                    $auc_id = end((explode('/', $td->find('a', 0)->href)));
                    $a_tr[] = $auc_id;
                }
                if ($i == 2 || $i == 6)
                {
                    $a_tr[] = trim(str_replace(static::$JP, static::$EN, strip_tags($td->innertext)));
                }
                else
                {
                    $a_tr[] = trim(strip_tags($td->innertext));
                }
            }
            
            $a_auctions[] = $a_tr;
        }

        $data['auctions'] = $a_auctions;
        
        return $data;
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
            throw new ParserException('Page POST form not found');
        }

        return $arr;
    }

    /**
     * Check result of bid.
     *
     * @param  string $body    Html page with bid result
     * @return bool            Return true if bid was success or false if unknown result (like won lots limit etc.)
     *
     * @throws ParserException Throw exception if bid was not success
     *
     */
    public static function getResult(&$body)
    {
        $html = static::getHtmlDom($body);

        if ($p_result = $html->find('div[id=modAlertBox]', 0))
        {
            if (static::$BID_SUCCESS == trim($p_result->find('strong', 0)->innertext))
            {
                return true;
            }
            else if (static::$AUCTION_WON == trim($p_result->find('strong', 0)->innertext))
            {
                return true;
            }
            else
            {
                throw new ParserException('Page says: '.$p_result->innertext);
            }
        }
        else if ($p_result = $html->find('div[class=decSmryTable]', 0))
        {
            if (preg_match(static::$PRICE_UP, $p_result))
            {
                throw new ParserException('Price goes up', 10);
            }
            else
            {
                throw new ParserException('Parser could not find result, maybe price goes up');
            }
        }

        return false;
    }

    /**
     * Create DOM tree from HTML
     *
     * @throws ParserException Throw exception if HTML is empty
     *
     */
    private static function getHtmlDom(&$body)
    {
        if (!$body)
        {
            throw new ParserException('Body of HTML Document is empty');
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
