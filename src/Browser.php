<?php

namespace Yahooauc;

use Yahooauc\Parser;
use Yahooauc\Exceptions\ApiException;
use Yahooauc\Exceptions\BrowserException;
use Yahooauc\Exceptions\BrowserLoginException;
use Requests_Session;
use Requests;

/**
 * Class use HTTP Requests for get HTML content from auctions.yahoo.co.jp
 * It include Login function into yahoo, bid on auction lot, get current bidding lots,
 * get won lots and manage your cookies
 * 
 * @category Browser
 * @package  Yahoo\Auction
 * @author   Zazimko Alexey <notfoundsam@gmail.com>
 * @link     https://github.com/notfoundsam/yahoo-auction
 *
 */
class Browser
{
    private $debug               = false;

    private $userName            = null;
    private $userPass            = null;
    private $appId               = null;

    private $session             = null;
    private $auctionInfo         = null;
    
    private $testPath            = null;

    private static $AUCTION_URL  = 'http://auctions.yahoo.co.jp/';
    private static $LOGIN_URL    = 'https://login.yahoo.co.jp/config/login';
    private static $CLOSED_USER  = 'https://auctions.yahoo.co.jp/closeduser/jp/show/mystatus';
    private static $OPEN_USER    = 'https://auctions.yahoo.co.jp/openuser/jp/show/mystatus';
    private static $BID_PREVIEW  = 'https://auctions.yahoo.co.jp/jp/show/bid_preview';
    private static $PLACE_BID    = 'https://auctions.yahoo.co.jp/jp/config/placebid';
    private static $API_URL      = 'https://auctions.yahooapis.jp/AuctionWebService/V2/auctionItem';

    private static $DEBUG_BID    = 'https://page.auctions.yahoo.co.jp/jp/auction/x000000000';

    private static $BROWSER_HEADERS = [
        'User-Agent' => 'Mozilla/6.0 (Windows; U; Windows NT 6.0; ja; rv:1.9.1.1) Gecko/20090715 Firefox/3.5.1 (.NET CLR 3.5.30729)',
    ];

    /**
     * Create Browser object with stored cookie session or create new session
     *
     * @param  string $userName  Your Yahoo account user name
     * @param  string $userPass  Your Yahoo account password
     * @param  string $appId     Your Yahoo application ID
     * @param  string $cookieJar Stored cookieJar object
     * @return object            Return setted Browser object
     *
     */
    public function __construct($userName, $userPass, $appId, $cookieJar = null, $requestOptons = [])
    {
        if ($env = getenv('YAHOO_AUC_ENV'))
        {
            if (strtolower($env) !== 'production')
            {
                $this->debug(true);
            }
        }

        $this->userName      = $userName;
        $this->userPass      = $userPass;
        $this->appId         = $appId;
        $this->requestOptons = $requestOptons;

        if ($cookieJar)
        {
            $this->session = new Requests_Session(static::$AUCTION_URL, static::$BROWSER_HEADERS, [], ['cookies' => $cookieJar]);
        }
        else
        {
            $this->login();
        }
    }

    /**
     * Debug mode to test your application locally
     *
     * @param bool $debug      Enable or disable debug mode
     * @param string $testPath Set the directory path with response files
     */
    public function debug($debug, $testPath = null)
    {
        $this->debug = $debug;
        $this->testsPath = $testPath ? $testPath : realpath(__DIR__.'/../tests/').DIRECTORY_SEPARATOR;
    }

    /**
     * Get Browser cookiesJar to store for the next request without login
     *
     */
    public function getCookie()
    {
        return $this->session->options['cookies'];
    }

    /**
     * Get XML odject by auction id
     *
     * @param  string $auc_id   Auction ID
     * @return SimpleXMLElement Return XML Object
     * @throws ApiException Throw exception if auction id is invalid or not found etc.
     *
     */
    public function getAuctionInfoAsXml($auc_id)
    {
        $query = [
            'appid' => $this->appId,
            'auctionID' => $auc_id,
        ];

        $body = $this->getBody(static::$API_URL, $query);

        $info = simplexml_load_string($body);

        if (isset($info->Code))
        {
            if ( (int) $info->Code == 301)
            {
                throw new ApiException('Auction not found', 301);
            }
            else if ( (int) $info->Code == 302 )
            {
                throw new ApiException('Auction ID is invalid', 302);
            }
        }
        else if (isset($info->Message))
        {
            throw new ApiException($info->Message, 403);
        }

        $this->auctionInfo = $info;
        
        return $info;
    }

    /**
     * Return images link of lot
     *
     * @param  string $auc_id Auction ID
     * @return array          Return array with images url from stored auctionInfo
     *
     */
    public function getAuctionImgsUrl($auc_id = null)
    {
        if ($auc_id)
        {
            $this->getAuctionInfoAsXml($auc_id);
        }

        if ($this->auctionInfo == null)
            return [];
        
        $imges = [];

        foreach ($this->auctionInfo->Result->Img->children() as $img)
        {
            $imges[] = (string) $img;
        }

        return $imges;
    }

    /**
     * Get XML odject by auction id
     *
     * @return SimpleXMLElement|null Return storred XML Object or null
     *
     */
    public function getStoredAuctionInfoAsXml()
    {
        return $this->auctionInfo;
    }

    /**
     * Return only won auction IDs per page
     *
     * @param  int $page Number of page with won lots
     * @return array     Return array with only won auction IDs
     *
     */
    public function getWonIds($page = 1)
    {
        $query = [
            'select'  => 'won',
            'picsnum' => '50',
            'apg'     => $page
        ];

        $body = $this->getBody(static::$CLOSED_USER, $query);

        return Parser::getWonIds($body);
    }

    /**
     * Get lots information on bid by setted page
     *
     * @param  int $page Number of bidding page
     * @return array     Return array with lot information and bidding pages if they exist
     *
     */
    public function getBiddingLots($page = 1)
    {
        $query = [
            'select'  => 'bidding',
            'picsnum' => '50',
            'apg'     => $page
        ];

        $body = $this->getBody(static::$OPEN_USER, $query);

        return Parser::getBiddingLots($body);        
    }

    /**
     * Bid on yahoo lot
     *
     * @param  string $auc_id auction ID
     * @param  int $price     Price to bid
     * @return bool           Reurn true if bid was successful
     * @throws BrowserException Throw exception if the auction isn't open
     *
     */
    public function bid($auc_id, $price = 0)
    {
        $info = $this->getAuctionInfoAsXml($auc_id);

        if ( (string) $info->Result->Status != 'open' )
        {
            throw new BrowserException('Auction has ended');
        }

        $auc_url = (string) $info->Result->AuctionItemUrl;

        $body = $this->getBody($auc_url);

        $inputs = Parser::getHiddenInputs($body);
        $options = $this->createBidRequstOptions($inputs, $price);

        $body = $this->getBody(static::$BID_PREVIEW, $options, Requests::POST);

        $inputs = Parser::getHiddenInputs($body);
        $options = $this->createBidRequstOptions($inputs, $price);

        $body = $this->getBody(static::$PLACE_BID, $options, Requests::POST);

        return Parser::getResult($body);
    }

    /**
     * Try to login into yahoo auction with setted credentials
     *
     * @return void
     * @throws BrowserLoginException Throw exception if something wrong
     *
     */
    private function login()
    {
        $this->session  = new Requests_Session(static::$AUCTION_URL, static::$BROWSER_HEADERS);

        $query = [
            '.lg' => 'jp',
            '.intl' => 'jp',
            '.src' => 'auc',
            '.done' => static::$AUCTION_URL,
        ];

        $this->getBody(static::$AUCTION_URL);

        $body = $this->getBody(static::$LOGIN_URL, $query);

        preg_match_all(
            '/document\.getElementsByName\("\.albatross"\)\[0\]\.value = "(.*?)";/',
            $body,
            $albatross,
            PREG_SET_ORDER
        );

        if (!$albatross[0][1])
        {
            throw new BrowserLoginException('Albatross key not found');
        }

        $inputs = Parser::getHiddenInputs($body);

        $options = [];

        foreach ($inputs as $v)
        {
            if ($v['name'] == '.nojs')
            {
                continue;
            }

            if ($v['name'] == '.albatross')
            {
                $v['value'] = $albatross[0][1];
            }

            $options[$v['name']] = $v['value'];
        }

        $options['login']       = $this->userName;
        $options['passwd']      = $this->userPass;
        $options['.persistent'] = 'y';

        /* Pause before submit (important because yahoo decline login if it's too fast) */
        if (!$this->debug) sleep(3);

        $body = $this->getBody(static::$LOGIN_URL, $options, Requests::POST);
        
        /* Check for correct login */
        if (Parser::checkLogin($body, $this->userName) === false )
        {
            throw new BrowserLoginException('Login failed');
        }
    }

    /**
     * Send request to Yahoo and return body of HTML
     *
     * @param  string $url    Target url
     * @param  array $options Query parameters if method is GET or data values if method is POST
     * @param  string $method Request method
     * @return string         Return body of HTML
     *
     */
    private function getBody($url, $options = null, $method = Requests::GET)
    {
        if ($this->debug) return $this->readFile($url, $options, $method);

        $response = $this->session->request($url, [], $options, $method, $this->requestOptons);

        return $response->body;
    }

    /**
     * Create new options for request by values recived from response
     *
     * @param  array   $values  Array with pair name and value
     * @param  integer $price   Bid price
     * @return array            Return options for request
     * @throws BrowserException Throw exception if given price lower than current
     *
     */
    private function createBidRequstOptions($values, $price)
    {
        $options = [];
        $price_setted = false;

        foreach ($values as $value)
        {
            if(!$value['name'])
                continue;

            if ($value['name'] == 'setPrice')
            {
                if ($price < $value['value'])
                {
                    throw new BrowserException('Price must be upper or equal '.$value['value']);
                }
            }

            /* Do not subscribe (if store) */
            if ($value['name'] == 'mnewsoptin')
            {
                $value['value'] = 0;
            }

            if ($value['name'] == 'Bid')
            {
                $value['value'] = $price;
                $price_setted = true;
            }

            $options[$value['name']] = $value['value'];
        }

        if (!$price_setted)
        {
            $options['Bid'] = $price;
        }

        $options['Quantity'] = 1;

        return $options;
    }

    /**
     * Return body of HTML from file when debug mode is true
     *
     * @param  string $url    Target url
     * @param  array $options Query parameters if method is GET or data values if method is POST
     * @param  string $method Request method
     * @return string         Return body of HTML
     *
     */
    private function readFile($url, $options, $method)
    {
        $testsPath = realpath(__DIR__.'/../tests/').DIRECTORY_SEPARATOR;

        switch ($url) {
            case static::$AUCTION_URL:
                return file_get_contents($testsPath.'auction_url.html');
            
            case static::$LOGIN_URL:
                return $method == Requests::GET ? 
                        file_get_contents($testsPath.'login_url_get.html') :
                        file_get_contents($testsPath.'login_url_post.html');
            
            case static::$API_URL:
                if ($options)
                {
                    if (!isset($options['appid']) || !isset($options['auctionID']))
                    {
                        return file_get_contents($testsPath.'api_bad_request.xml');
                    }
                    if ($options['appid'] != 'app_id_random_hash')
                    {
                        return file_get_contents($testsPath.'api_forbidden.xml');
                    }
                    if (!preg_match('/[a-z]{1}[0-9]{9}/', $options['auctionID']))
                    {
                        return file_get_contents($testsPath.'api_302.xml');
                    }
                    if (!in_array($options['auctionID'], ['e000000000', 'x000000000']))
                    {
                        return file_get_contents($testsPath.'api_301.xml');
                    }
                    if ($options['auctionID'] == 'e000000000')
                    {
                        return file_get_contents($testsPath.'api_url_ended.xml');
                    }
                }
                return file_get_contents($testsPath.'api_url.xml');
            
            case static::$OPEN_USER:
                return file_get_contents($testsPath.'open_user.html');
            
            case static::$CLOSED_USER:
                return file_get_contents($testsPath.'closed_user.html');

            case static::$DEBUG_BID:
                return file_get_contents($testsPath.'bid_info.html');

            case static::$BID_PREVIEW:
                return file_get_contents($testsPath.'bid_preview.html');

            case static::$PLACE_BID:
                if ($options && isset($options['Bid']) && (int) $options['Bid'] >= 1000)
                {
                    return file_get_contents($testsPath.'place_bid.html');
                }
                return file_get_contents($testsPath.'place_bid_price_up.html');
            
            default:
                break;
        }

        return '';
    }
}
