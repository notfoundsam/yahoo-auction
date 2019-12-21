<?php

require __DIR__ . '/../vendor/autoload.php';

use Yahooauc\Browser as Browser;

$userName = "test_user";
$userPass = "secret_password";
$appId    = "app_id_random_hash";

/* Get saved cookies */
$cookie = file_get_contents('cookie.cache');
$cookieJar = $cookie !== false ? unserialize($cookie) : [];

try {
    $browser = new Browser($userName, $userPass, $appId, $cookieJar);

    /* Get information about the lot */
    var_dump($browser->getAuctionInfoAsXml("x000000000"));

    /* Get images of the lot */
    var_dump($browser->getAuctionImgsUrl("x000000000"));

    /* Get list of lots from the first bidding page */
    var_dump($browser->getBiddingLots(1));

    /* Get IDs of lots from the first won page */
    var_dump($browser->getWonIds(1));

    /* Bid on the lot */
    var_dump($browser->bid("x000000000", 1000));
    
    /* Save the latest cookies */
    $cookieJar = $browser->getCookie();
    $cookie = serialize($cookieJar);
    file_put_contents('cookie.cache', $cookie);
} catch (BrowserLoginException $e) {
    echo("Login exception\n");
} catch (Exception $e) {
    $m = trim($e->getMessage());
    echo("{$m}\n");

    /* Save the latest cookies */
    file_put_contents('cookie.cache', $cookie);
}
