<?php

namespace Yahooauc;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use SimpleXMLElement;
use Yahooauc\Exceptions\ApiException;
use Yahooauc\Exceptions\CaptchaException;
use Yahooauc\Exceptions\AuctionEndedException;
use Yahooauc\Exceptions\LoggedOffException;
use Yahooauc\Exceptions\LoginException;
use Yahooauc\Exceptions\BrowserException;
use Yahooauc\Exceptions\ParserException;
use Yahooauc\Exceptions\RebidException;

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
    private $userName;
    private $userPass;
    private $appId;
    private $requestOptions;

    private $client;
    private $auctionInfo            = null;
    private $captchaId              = null;
    private $loginWithCaptcha       = false;

    private $debug                  = false;
    private $debugShowCaptcha       = false;
    private $debugYahooBlocked      = false;
    private $testsPath              = null;

    private static $AUCTION_URL     = 'http://auctions.yahoo.co.jp/';
    private static $LOGIN_CHECK_URL = 'https://auctions.yahoo.co.jp/';
    private static $LOGIN_URL       = 'https://login.yahoo.co.jp/config/login';
    private static $CLOSED_USER     = 'https://auctions.yahoo.co.jp/closeduser/jp/show/mystatus';
    private static $OPEN_USER       = 'https://auctions.yahoo.co.jp/openuser/jp/show/mystatus';
    private static $BID_PREVIEW     = 'https://auctions.yahoo.co.jp/jp/show/bid_preview';
    private static $PLACE_BID       = 'https://auctions.yahoo.co.jp/jp/config/placebid';
    private static $API_URL         = 'https://auctions.yahooapis.jp/AuctionWebService/V2/auctionItem';

    private static $DEBUG_BID       = 'https://page.auctions.yahoo.co.jp/jp/auction/x000000000';

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
     * @return void
     *
     */
    public function __construct($userName, $userPass, $appId, $cookieJar = null, $requestOptions = [])
    {
        if ($env = getenv('YAHOO_AUC_ENV')) {
            if (strtolower($env) !== 'production') {
                $this->debug(true);
            }
        }

        $this->userName       = $userName;
        $this->userPass       = $userPass;
        $this->appId          = $appId;
        $this->requestOptions = $requestOptions;

        $cookies = $cookieJar ? $cookieJar : new CookieJar;
        $this->client = new Client(['cookies' => $cookies, 'headers' => static::$BROWSER_HEADERS]);
    }

    /**
     * Debug mode to test your application locally
     *
     * @param bool   $debug    Enable or disable debug mode
     * @param string $testsPath Set the directory path with response files
     */
    public function debug($debug, $testsPath = null)
    {
        $this->debug = $debug;
        $this->testsPath = $testsPath ? $testsPath : realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'tests').DIRECTORY_SEPARATOR;
    }

    /**
     * @param $showCaptcha
     */
    public function debugShowCaptcha($showCaptcha)
    {
        $this->debugShowCaptcha = $showCaptcha;
    }

    /**
     * @param $yahooBlocked
     */
    public function debugYahooBlocked($yahooBlocked)
    {
        $this->debugYahooBlocked = $yahooBlocked;
    }

    /**
     * Get Browser cookiesJar to store for the next request without login
     */
    public function getCookie()
    {
        return $this->client->getConfig('cookies');
    }

    /**
     * Get XML object by auction id
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
     * @throws ApiException   Throw exception if has API error
     */
    public function getAuctionImgsUrl($auc_id = null)
    {
        if ($auc_id) {
            $this->getAuctionInfoAsXml($auc_id);
        }

        if ($this->auctionInfo == null) return [];
        
        $images = [];

        foreach ($this->auctionInfo->Result->Img->children() as $img) {
            $images[] = (string) $img;
        }

        return $images;
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
     * @throws LoggedOffException
     */
    public function getWonIds($page = 1)
    {
        if (!$this->checkLogin()) throw new LoggedOffException;

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
     * @throws LoggedOffException
     */
    public function getBiddingLots($page = 1)
    {
        if (!$this->checkLogin()) throw new LoggedOffException;

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
     * @param  string $auc_id        auction ID
     * @param  int $price            Price to bid
     * @return bool                  Return true if bid was successful
     * @throws ApiException          Throw exception if has API error
     * @throws BrowserException      Throw exception if something wrong
     * @throws RebidException        Throw exception if price of bid under then current price
     * @throws AuctionEndedException Throw exception if auction has already closed
     *
     */
    public function bid($auc_id, $price = 0)
    {
        $info = $this->getAuctionInfoAsXml($auc_id);

        if ( (string) $info->Result->Status != 'open' ) {
            throw new AuctionEndedException;
        }

        $auc_url = (string) $info->Result->AuctionItemUrl;
        $body = $this->getBody($auc_url);

        try {
            $inputs = Parser::getHiddenInputs($body);
        } catch (ParserException $e) {
            throw new BrowserException($e->getMessage(), $e->getCode());
        }

        $options = $this->createBidRequestOptions($inputs, $price);
        $body = $this->getBody(static::$BID_PREVIEW, $options, 'POST');

        try {
            $inputs = Parser::getHiddenInputs($body);
        } catch (ParserException $e) {
            throw new BrowserException($e->getMessage(), $e->getCode());
        }

        $options = $this->createBidRequestOptions($inputs, $price);
        $body = $this->getBody(static::$PLACE_BID, $options, 'POST');

        try {
            $result = Parser::getResult($body);
        } catch (ParserException $e) {
            throw new BrowserException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    /**
     * Try to login into yahoo auction with credentials
     *
     * @return bool             Return true if success
     * @throws LoginException
     * @throws CaptchaException
     *
     */
    public function login()
    {
        $query = [
            '.lg' => 'jp',
            '.intl' => 'jp',
            '.src' => 'auc',
            '.done' => static::$AUCTION_URL,
        ];

        $this->getBody(static::$AUCTION_URL);

        $body = $this->getBody(static::$LOGIN_URL, $query);

        if (!$ak = $this->getAlbatrossKey($body)) {
            throw new LoginException('Albatross key not found');
        }

        try {
            $inputs = Parser::getHiddenInputs($body);
        } catch (ParserException $e) {
            throw new LoginException($e->getMessage(), $e->getCode());
        }
        $options = $this->createLoginOptions($inputs, $ak);

        /* Pause before submit (important because yahoo decline login if it's too fast) */
        if (!$this->debug) sleep(3);

        $body = $this->getBody(static::$LOGIN_URL, $options, 'POST');

        if ($this->checkLogin($body)) {
            return true;
        }

        if ($this->isCaptchaRequired($body)) {
            file_put_contents('captcha.html', $body);
            throw new CaptchaException;
        }

        if (strpos($body, 'In order to prevent unauthorized access, your access to Yahoo! JAPAN has been restricted.') !== false) {
            throw new LoginException('Yahoo blocked your account for a while');
        }

        file_put_contents('login_fail.html', $body);

        throw new LoginException('Unexpected behavior');
    }

    /**
     * Try to login into yahoo auction with the captcha and credentials
     * Currently doesn't work
     *
     * @param string $captchaId     Captcha ID
     * @param string $captchaAnswer Answer with letters
     * @return bool                 Return true if success
     * @throws LoginException
     * @throws ParserException
     */
    public function loginWithCaptcha($captchaId, $captchaAnswer)
    {
        $this->loginWithCaptcha = true;
        $options = $this->createCaptchaOptions($captchaId, $captchaAnswer);
        $body = $this->getBody(static::$LOGIN_URL, $options, 'POST');

        file_put_contents('001.html', $body);
        if (!$ak = $this->getAlbatrossKey($body)) {
            throw new LoginException('Albatross key not found after captcha');
        }

        $inputs = Parser::getHiddenInputs($body);
        $options = $this->createLoginOptions($inputs, $ak);
        $this->loginWithCaptcha = false;

        /* Pause before submit (important because yahoo decline login if it's too fast) */
        if (!$this->debug) sleep(3);

        $body = $this->getBody(static::$LOGIN_URL, $options, 'POST');
        file_put_contents('002.html', $body);

        if ($this->checkLogin($body)) {
            return true;
        }

        throw new LoginException('Login with captcha failed');
    }

    /**
     * @param string $body Body of the document
     * @return bool        Return true if logged in
     */
    public function checkLogin(&$body = null)
    {
        if ($body === null) {
            $body = $this->getBody(static::$LOGIN_CHECK_URL);
        }

        return Parser::checkLogin($body, $this->userName);
    }

    /**
     * @param string $body Body of the document
     * @return bool        Return true if a captcha required
     */
    private function isCaptchaRequired(&$body)
    {
        $id = Parser::getCaptchaId($body);

        if ($id !== null) {
            $this->captchaId = $id;
            return true;
        }

        return false;
    }

    /**
     * @return string|null Return the captcha ID or null
     */
    public function getCaptchaId()
    {
        return $this->captchaId;
    }

    /**
     * @return string|null Return the captcha URL or null
     */
    public function getCaptchaUrl()
    {
        return $this->captchaId ? 'https://ncaptcha.yahoo.co.jp/v1/img/'.$this->captchaId : null;
    }

    /**
     * @param string  $body Body of the document
     * @return string       Return the albatross key
     */
    private function getAlbatrossKey(&$body)
    {
        preg_match_all(
            '/document\.getElementsByName\("\.albatross"\)\[0\]\.value = "(.*?)";/',
            $body,
            $albatross,
            PREG_SET_ORDER
        );

        return $albatross[0][1];
    }

    /**
     * @param array  $inputs Inputs of the login form
     * @param string $ak     Albatross key
     * @return array         Return an array of form options
     */
    private function createLoginOptions(&$inputs, $ak)
    {
        $options = [];

        foreach ($inputs as $v)
        {
            if ($v['name'] == '.nojs')
            {
                continue;
            }

            if ($v['name'] == '.albatross')
            {
                $v['value'] = $ak;
            }

            $options[$v['name']] = $v['value'];
        }

        $options['login']       = $this->userName;
        $options['user_name']   = $this->userName;
        $options['passwd']      = $this->userPass;
        $options['.persistent'] = 'y';
        $options['auth_method'] = 'pwd';
        $options['auth_list']   = 'pwd';
        $options['fido']        = '0';

        return $options;
    }

    /**
     * @param string $captchaId     Captcha ID
     * @param string $captchaAnswer Answer with letters
     * @return array                Return an array of form options
     */
    private function createCaptchaOptions($captchaId, $captchaAnswer)
    {
        $options = [
            '.src' => 'auc',
            '.done' => static::$AUCTION_URL,
            '.display' => '',
            'ckey' => '',
            'auth_lv' => 'pw',
            'validate' => 'validate',
            'captchaId' => $captchaId,
            '.sectry' => '0',
            'captchaAnswer' => $captchaAnswer,
            'x' => '115',
            'y' => '17',
        ];

        return $options;
    }

    /**
     * Send request to Yahoo and return body of HTML
     *
     * @param  string $url     Target url
     * @param  array  $options Query parameters if method is GET or data values if method is POST
     * @param  string $method  Request method
     * @return string          Return body of HTML
     *
     */
    private function getBody($url, $options = [], $method = 'GET')
    {
        if ($this->debug) return $this->readFile($url, $options, $method);

        if ($method === 'GET') {
            $response = $this->client->get($url, ['query' => $options]);
        } else if ($method === 'POST') {
            $response = $this->client->post($url, ['form_params' => $options]);
        }

        return $response->getBody()->getContents();
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
    private function createBidRequestOptions($values, $price)
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
                    throw new BrowserException('Price must be upper or equal '.$value['value'], 20);
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
     * @param  string $url     Target url
     * @param  array  $options Query parameters if method is GET or data values if method is POST
     * @param  string $method  Request method
     * @return string          Return body of HTML
     *
     */
    private function readFile($url, $options, $method)
    {
        $testsPath = realpath(__DIR__.'/../tests/').DIRECTORY_SEPARATOR;

        switch ($url) {
            case static::$AUCTION_URL:
                return file_get_contents($testsPath.'auction_url.html');
            
            case static::$LOGIN_URL:
                if ($method == 'GET') {
                    return file_get_contents($testsPath.'login_url_get.html');
                } else {
                    if ($this->debugShowCaptcha) {
                        return file_get_contents($testsPath.'login_captcha.html');
                    } else if ($this->debugYahooBlocked) {
                        return file_get_contents($testsPath.'login_blocked.html');
                    } else {
                        return $this->loginWithCaptcha ?
                            file_get_contents($testsPath.'login_url_get.html') :
                            file_get_contents($testsPath.'login_url_post.html');
                    }
                }

            case static::$LOGIN_CHECK_URL:
                return file_get_contents($testsPath.'login_url_post.html');

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
