<?php

require __DIR__ . '/../vendor/autoload.php';

use Yahooauc\Browser as Browser;
use Yahooauc\Exceptions\AuctionEndedException;
use Yahooauc\Exceptions\BrowserException;
use Yahooauc\Exceptions\CaptchaException;
use Yahooauc\Exceptions\LoggedOffException;
use Yahooauc\Exceptions\LoginException;

$userName = "test_user";
$userPass = "secret_password";
$appId    = null;

$captchaId = "AgAAAIkBAAABAAAAAAAAAAABA";
$captchaAnswer = "くやひよかとむちひな";

/* Get saved cookie */
$cookie = file_get_contents('cookie.cache');
$cookieJar = $cookie !== false ? unserialize($cookie) : [];

$browser = new Browser($userName, $userPass, $appId, $cookieJar);

/* Set the debug mode */
$browser->debug(true);

/* Emulate very many attempts to login */
// $browser->debugShowCaptcha(true);

/* Emulate too many attempts to login and get ban */
// $browser->debugYahooBlocked(true);

/* Check is logged in */
var_dump($browser->checkLogin());

try {
    /* Try to login into Yahoo */
    var_dump($browser->login());

    /* Try to login into Yahoo (Doesn't support yet) */
    // var_dump($browser->loginWithCaptcha($captchaId, $captchaAnswer));

    /* Get information about lot */
    var_dump($browser->getAuctionInfoAsXml("e000000000"));

    /* Get list of lots from first bidding page */
    var_dump($browser->getBiddingLots(1));

    /* Get IDs of lots from first won page */
    var_dump($browser->getWonIds(1));

    /* Bid on lot */
    var_dump($browser->bid("x000000000", 100));

    /* Save latest cookie */
    $cookieJar = $browser->getCookie();
    $cookie = serialize($cookieJar);
    file_put_contents('cookie.cache', $cookie);
} catch (LoginException $e) {
    echo trim($e->getMessage())."\n";
} catch (LoggedOffException $e) {
    echo trim($e->getMessage())."\n";
} catch (AuctionEndedException $e) {
    echo trim($e->getMessage())."\n";
} catch (BrowserException $e) {
    echo trim($e->getMessage())."\n";
} catch (CaptchaException $e) {
    echo $e->getMessage()."\n";
    echo $browser->getCaptchaId()."\n";
    echo $browser->getCaptchaUrl()."\n";

    /* Save the latest cookies */
    $cookieJar = $browser->getCookie();
    $cookie = serialize($cookieJar);
    file_put_contents('cookie.cache', $cookie);
} catch (Exception $e) {
    echo trim($e->getMessage())."\n";
}
