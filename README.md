# Yahoo Auction Http library

### Description
This library will help you to work with Yahoo auction. It makes requests to Yahoo auction like a normal browser. To use this library you must specify your username and password from Yahoo to login. In order not to make a login every time, after the first login, save your cookies so that the following requests will be in the same session. You can view your won lots and bidding lots. To get information about the lot or bid on the lot, you will need an application key.

### Requirements
- php >= 5.4
- php-curl
- php-mbstring
- php-xml

### Installation
```
composer require notfoundsam/yahoo-auction
```

### Examples
Specify your credentials and create a new instance of Browser object.
```
require __DIR__ . '/../vendor/autoload.php';

use Yahooauc\Browser as Browser;

$userName = "your_yahoo_user";
$userPass = "your_yahoo_pass";
$appId    = "your_app_id";

/* Get saved cookie */
$cookie = file_get_contents('cookie.cache');
$cookieJar = $cookie !== false ? unserialize($cookie) : [];

$browser = new Browser($userName, $userPass, $appId, $cookieJar);
```
Use the next methods to get information about your auctions or bid on the lot.
```
/* Get information about the lot */
var_dump($browser->getAuctionInfoAsXml("auction_lot_id"));

/* Get list of lots from the first bidding page */
var_dump($browser->getBiddingLots(1));

/* Get array of auction id from the first won page */
var_dump($browser->getWonIds(1));

/* Bid on the lot. The second argument is price in yen */
var_dump($browser->bid("auction_lot_id", 100));
```
In the end save your browser cookies to use it next time.
```
$cookieJar = $browser->getCookie();
$cookie = serialize($cookieJar);
file_put_contents('cookie.cache', $cookie);
```

### Debug
Since v1.1.0 you can use the debug mode to test your application locally. Export `YAHOO_AUC_ENV` to your environment. Set it to `production` to use on production or other to use the debug mode.
```
export YAHOO_AUC_ENV=local
```
You can also enable or disable the debug mode with the following method.  
Pass the second argument with the path to the folder with your test files.
```
$browser->debug($debug = true);
$browser->debug($debug = true, $testPath = 'your_folder_with_test_pages');
```
### How to use the debug mode
Replace `test_user` with something else to throw `BrowserLoginException`. It means login failed.
```
$userName = "not_test_user";
$userPass = "secret_password";
$appId    = "app_id_random_hash";

$browser = new Browser($userName, $userPass, $appId, []);
```
Replace `app_id_random_hash` with something else to throw `ApiException`.  
Pass something other than the following format `x000000000` to throw `ApiException`. It means the auction ID is invalid.  
Pass something in the following format `x000000000` like `x000000001` to throw `ApiException`. It means the auction not found.
```
$userName = "test_user";
$userPass = "secret_password";
$appId    = "not_app_id_random_hash";

$browser = new Browser($userName, $userPass, $appId, []);
$browser->getAuctionInfoAsXml("xxxxxxx01");
$browser->getAuctionInfoAsXml("x000000001");
```
Get an array of fake data from the first bidding page.
```
$userName = "test_user";
$userPass = "secret_password";
$appId    = "app_id_random_hash";

$browser = new Browser($userName, $userPass, $appId, []);
$browser->getBiddingLots(1);
```
Get an array of fake IDs from the first won page.
```
$userName = "test_user";
$userPass = "secret_password";
$appId    = "app_id_random_hash";

$browser = new Browser($userName, $userPass, $appId, []);
$browser->getWonIds(1);
```
Bid on the following lot `e000000000` to throw `BrowserException`. This auction has alredy ended.  
Bid on the following lot `x000000000` with price under `220` to throw `BrowserException`. It means your price is lower than the current price.  
Bid on the following lot `x000000000` with price between `220` and `999` to throw `RebidException`. It means the price of the lot rose higher and the bid failed.  
Bid on the following lot `x000000000` with price more than `999` for a successufull bid.
```
$userName = "test_user";
$userPass = "secret_password";
$appId    = "app_id_random_hash";

$browser = new Browser($userName, $userPass, $appId, []);
$browser->bid("e000000000", 1000); // Has already ended
$browser->bid("x000000000", 100);  // Not enough
$browser->bid("x000000000", 500);  // Rebid page, bid failed
$browser->bid("x000000000", 1000); // Success
```

## About v1.1.0

### Features
- Added the debug mode.

### Bugfixes
- Did not throw an exception if the rebid page is shown.

### Updates
- Improved work with Yahoo API.
- Compatibility for php 7.3.

### Notes
- Replace `sunra/php-simple-html-dom-parser` with `Kub-AT/php-simple-html-dom-parser` to support php 7.3.

### Migration from v1.0.x
- Method `getAuctionInfoAsXml()` now throw `ApiException` instead of `BrowserException`.
- If you want to use the debug mode add `YAHOO_AUC_ENV` to your environment.
