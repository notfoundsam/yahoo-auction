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

/* Get saved cookie */
$cookie = file_get_contents('cookie.cache');
$cookieJar = $cookie !== false ? unserialize($cookie) : [];

$browser = new Browser($userName, $userPass, null, $cookieJar);
```
If you don't have cookies yet try to login into Yahoo. 
It throws `LoginException` or `CaptchaException` if something wrong.
```
/* Try to login into Yahoo */
var_dump($browser->login());
```
If you already have cookies try to check it.
```
/* Check is logged in */
var_dump($browser->checkLogin());
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
Since v1.1.0 you can use the debugging mode to test your application locally. Export `YAHOO_AUC_ENV` to your environment. Set it to `production` to use on production or other to use the debugging mode.
```
export YAHOO_AUC_ENV=local
```
You can also enable or disable the debugging mode with the following method.  
Pass the second argument with the path to the folder with your test files.
```
$browser->debug($debug = true);
$browser->debug($debug = true, $testPath = 'your_folder_with_test_pages');
```
### How to use the debugging mode
Replace `test_user` with something else to throw `LoginException`. It means the login failed.
```
$userName = "not_test_user";
$userPass = "secret_password";

$browser = new Browser($userName, $userPass, null, []);
$browser->debug($debug = true);
```  
Pass the following id `n000000000` to throw `PageNotfoundException`. It means the auction not found.
```
$userName = "test_user";
$userPass = "secret_password";

$browser = new Browser($userName, $userPass, null, []);
$browser->debug($debug = true);
$browser->getAuctionInfoAsXml("n000000000");
```
Get an array of fake data from the first bidding page.
```
$userName = "test_user";
$userPass = "secret_password";

$browser = new Browser($userName, $userPass, null, []);
$browser->debug($debug = true);
$browser->getBiddingLots(1);
```
Get an array of fake IDs from the first won page.
```
$userName = "test_user";
$userPass = "secret_password";

$browser = new Browser($userName, $userPass, null, []);
$browser->debug($debug = true);
$browser->getWonIds(1);
```
Bid on the following lot `e000000000` to throw `AuctionEndedException`. This auction has already ended.  
Bid on the following lot `x000000000` with price under `220` to throw `BrowserException`. It means your price is lower than the current price.  
Bid on the following lot `x000000000` with price between `220` and `999` to throw `RebidException`. It means the price of the lot has rose, and the bid failed.  
Bid on the following lot `x000000000` with price more than `999` for a successful bid.
```
$userName = "test_user";
$userPass = "secret_password";

$browser = new Browser($userName, $userPass, null, []);
$browser->debug($debug = true);
$browser->bid("e000000000", 1000); // Has already ended
$browser->bid("x000000000", 100);  // Not enough
$browser->bid("x000000000", 500);  // Rebid page, bid failed
$browser->bid("x000000000", 1000); // Success
```

## About v1.3.x

### Features
- Added xdebug to the docker container.

### Updates
- Yahoo auction API was removed because Yahoo fully closed their API.
- If the page or lot not found it will throw `PageNotfoundException`.

### Notes
- Field `$appId` don't need anymore, pass null instead to the `Browser` constructor.
- Method `$browser->getAuctionInfoAsXml("...")` returns shorted version of API result. Currently, available fields: `AuctionID`, `AuctionItemUrl`, `Title`, `Seller->Id`, `Img`, `Price`, `TaxinPrice`, `StartTime`, `EndTime`, `Status`.

### Migration from v1.2.x
- Check available fields for `$browser->getAuctionInfoAsXml("...")` in Notes.
