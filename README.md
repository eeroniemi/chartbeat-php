chartbeat-php
=============
PHP library to interact with Chartbeat API.

See great [API docs](http://chartbeat.com/docs/api/) and [API explorer](http://chartbeat.com/docs/api/explore/) from [Chartbeat website](http://chartbeat.com) for more info.

This library requires PHP >5.3 and cURL extension for PHP to work. Tested with PHP 5.3.15 and 5.4.6

Please give feedback, report bugs, fork and contribute.

## Installation

Add chartbeat-php to your composer.json:

```php
{
	"require" : {
		"chartbeat/chartbeat-php" : "1.*"
	}
}
```

Simple test script:

```php
require_once ('vendor/autoload.php'); // composer autoloader
$apiKey = '<your api key>';
$host = '<your host>';
$cb = new Chartbeat\Chartbeat($host, $apiKey);

// get monthly max people on site
echo 'Monthly max people on the site: ' . $cb->getMonthlyMaxPeople(); 
```

You can also do any kind of API query using get() method. First parameter is data endpoint (eg. 'historical/traffic/series'), seconds one contains parameters.

In here, we get top referer of page which has most visitors right now:

```php
$cbData = $cb->get('live/toppages/v3/', array('limit' => 1)); // get all top pages, we want only 1
$topPage = current($cbData->pages); // get first page of the result
var_dump(current($topPage->stats->toprefs)); // output first top referer of page

// outputs:
object(stdClass)#9 (2) {
  ["visitors"]=>
  int(10)
  ["domain"]=>
  string(10) "google.com"
}
```

## Error handling

Every error throws exception Chartbeat\Exception. 

```php
try {
	// set invalid key and try to get data
	$cb->setApiKey('invalidapikey');
	$cb->getMonthlyMaxPeople(); 
} catch (Chartbeat\Exception $ex)
{
	echo "Caught exception: " . $ex->getMessage();
	// will output "Caught exception: Chartbeat API error. Message: No Access, Code: 403"
}
```

## Testing

You can test this library by running example.php with: 

    ./example.php <your host> <your API key> 

This will output monthly max people your site
	
