<?php

require __DIR__ . '/../vendor/autoload.php';

use Yahooauc\Browser as Browser;

$userName = "your_yahoo_user";
$userPass = "your_yahoo_pass";
$appId    = "your_app_id";

/* Get saved cookie */
$cookie = file_get_contents('cookie.cache');
$cookieJar = $cookie !== false ? unserialize($cookie) : [];

$browser = new Browser($userName, $userPass, $appId, $cookieJar);

/* Get information about lot */
var_dump($browser->getAuctionInfoAsXml("lotId"));

/* Get list of lots from first bidding page */
var_dump($browser->getBiddingLots(1));

/* Get IDs of lots from first won page */
var_dump($browser->getWonIds(1));

/* Bid on lot */
var_dump($browser->bid("lotId", 100));

/* Save latest cookie */
$cookieJar = $browser->getCookie();
$cookie = serialize($cookieJar);
file_put_contents('cookie.cache', $cookie);
