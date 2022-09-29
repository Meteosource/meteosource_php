meteosource - Weather API library
==========

PHP wrapper library for [Meteosource weather API](https://www.meteosource.com) that provides detailed hyperlocal weather forecasts for any location on earth.


### Installation
Install using [Composer](https://getcomposer.org/).

```bash
composer require meteosource/meteosource_php
```

### Get started

To use this library, you need to obtain your Meteosource API key. You can [sign up](https://www.meteosource.com/client/sign-up) or get the API key of existing account in [your dashboard](https://www.meteosource.com/client).

# Library usage

## Initialization

To initialize the `Meteosource` object, you need your API key and the name of your subscription plan (tier). Basic example of initialization is shown below:

```php
<?php
// Change this to your actual API key
const YOUR_API_KEY = 'abcdefghijklmnopqrstuvwxyz0123456789ABCD';
// Change this to your actual tier
const YOUR_TIER = 'flexi';

// Initialize the main Meteosource object
$meteosource = new Meteosource\Meteosource(YOUR_API_KEY, YOUR_TIER);
```

## Get the weather data

Using `meteosource` library, you can get weather forecasts or archive weather data (if you have a paid subscription).

### Forecast
To get the weather data for given place, use `getPointForecast()` method of the `Meteosource` object. You have to specify either the coordinates of the place (`lat` + `lon`) or the `place_id`. Detailed description of the parameters can be found in the [API documentation](https://www.meteosource.com/documentation).

Note that the default timezone is always `UTC`, as opposed to the API itself (which defaults to the point's local timezone). This is because the library always queries the API for the `UTC` timezone to avoid ambiguous datetimes problems. If you specify a different timezone, the library still requests the API for `UTC`, and then converts the datetimes to desired timezone.

```php
<?php
// Get the forecast for given point
$forecast = $meteosource->getPointForecast(
    null,  // You can specify place_id instead of lat+lon
    37.7775,  // Latitude of the point
    -122.416389,  // Longitude of the point
    ['current', 'hourly'],  // Defaults to '("current", "hourly")'
    'US/Pacific',  // Defaults to 'UTC', regardless of the point location
    'en',  // Defaults to 'en'
    'auto'  // Defaults to 'auto'
);
```

### Historical weather
Users with paid subscription to Meteosource can retrieve historical weather from `time_machine` endpoint, using `getTimeMachine()` method:

```php
<?php
// Get the historical weather
$timeMachine = $meteosource->getTimeMachine(
    '2019-12-25', // You can also pass array of dates, which can be string or DateTime objects
    null, // Start date - you can specify the range for dates you need, instead of array
    null, // End date  - you can specify the range for dates you need, instead of array
    'london', // ID of the place you want the historical weather for
    null, // You can specify lat instead of place_id
    null, // You can specify lon instead of place_id
    'UTC', // Defaults to 'UTC', regardless of the point location
    'us' // Defaults to 'auto'
);
```
Note, that the historical weather data are always retrieved for full UTC days. If you specify a different timezone, the datetimes get converted, but they will cover the full UTC, not the local day. If you specify a `datetime` to any of the date parameters, the hours, minutes, seconds and microseconds get ignored. So if you request `'2021-12-25T23:59:59'`, you get data for full UTC day `2021-12-25`.

If you pass `array` of dates to `date` parameter, they days will be inserted into the inner structures in the order they are being iterated over. This affects time indexing by integer (see below). An API request is made for each day, even when you specify a date range.

## Working with the weather data
All of the meteosource's data objects have overloaded `__toString()` methods, so you can `echo` the objects to get useful information about them:
```php
<?php
echo $forecast;  // <Forecast for lat: 37.7775, lon: -122.416389>
echo $timeMachine;  // <TimeMachine for lat: 51.50853, lon: -0.12574>
```

### Attribute access

The library loads the JSON response into its internal structures. You can access the attributes using the object operator (`->`), or the index operator (`[]`):

```php
<?php
// You can access all of the attributes with object operator:
echo $forecast->lat; // 37.7775

// ... or with index operator:
echo $forecast['lon'];  // -122.416389

// There is also information about the elevation of the point and the timezone
echo $timeMachine->elevation;  // 82
echo $timeMachine->timezone;  // 'UTC'
```

### Weather data sections

There are 5 weather forecast sections (`current`, `minutely`, `hourly`, `daily` and `alerts`) as attributes in the `Forecast` object.

The `current` data contains data for many variables for a single point in time (it is represented by `SingleTimeData` object):

```php
<?php
// <Instance of SingleTimeData (current) with 17 member variables (cloud_cover,
//  dew_point, feels_like, humidity, icon, icon_num, irradiance, ozone,
//  precipitation, pressure, summary, temperature, uv_index, visibility,
// wind, wind_chill)>
echo $forecast->current;
```

The `minutely`, `hourly` and `daily` sections contain forecasts for more points in time (represented by `MultipleTimesData`). The sections that were not requested are empty (null):
```php
<?php
echo $forecast->minutely  // null
```

The sections that were requested can also be `echo`ed, to view number of available timesteps and their range (inclusive):
```php
<?php
// <Hourly data with 164 timesteps
//  from 2021-09-08T22:00:00 to 2021-09-15T17:00:00>
echo $forecast->hourly;
```

The `alerts` section contain meteorological alerts and warnings, if there are any issued for the location. The `alerts` object is an instance of `AlertsData` class. You can echo the object or iterate over it:
```php
<?php
echo $forecast->alerts; // <Alerts data containing 4 alerts>
foreach($alerts as $alert) {
    // <Instance of SingleTimeData with 8 member variables
    //  (certainty, description, event, expires, headline, onset, sender, severity)>
    echo $alert;
}
```
You can also get list of all active alerts for given time. If you use `string` or tz-naive `DateTime` in this function, it will suppose that it is in the same timezone as requested for the forecast.
```php
<?php
// If you pass no parameter, it checks for current time
$forecast->alerts->getActive(); // returns list of SingleTimeData instances
// You can use either string...
$forecast->alerts->getActive('2022-03-08T22:00:00');
// ... or datetime (both tz-aware and naive)
$forecast->alerts->getActive(new DateTime('2022-03-08T22:00:00'));
```

There is a single section `data` for historical weather as an attribute in the `TimeMachine` object, represented by `MultipleTimesData`.
```php
<?php
echo $timeMachine->data  // <Instance of MultipleTimesData (time_machine) with 24 timesteps from 2019-12-25T00:00:00 to 2019-12-25T23:00:00>
```

### Time indexing

As mentioned above, the `minutely`, `hourly`, `daily` sections of `Forecast` and the `data` section of `TimeMachine` contain data for more timesteps. To get the data for a single time, you have several options.

  **1. Indexing with integer**

You can simply index an instance of `MultipleTimesData` with `integer`, as the offset from the current time:

```php
<?php
$forecast->hourly[0];
$timeMachine->data[0];
```

  **2. Indexing with string**

To get the exact time, you can use `string` in `YYYY-MM-DDTHH:00:00` format. The datetime string is assumed to be in the same timezone as the data.
```php
<?php
// Get a date in near future for which there are data in the current API response
$currentDate = (new DateTime())->add(new DateInterval('PT1H'));
$forecast->hourly[$currentDate];

// Get historical weather
$timeMachine->data['2019-12-25T03:00:00']
```

  **3. Indexing with `datetime`**

You can also use `DateTime` as an index, it is automatically converted to the timezone of the data.

```php
<?php
// Get a date in near future for which there are data in the current API response
$currentDt = new DateTime("Y-m-d\TH:00:00");

// Index with DateTime
$forecast->hourly[$currentDt]->temperature;

// Get historical weather
$timeMachine->data[new DateTime('2019-12-25')];
```

Note: *`minutely`, `hourly`, `daily` and `alerts` are of [ArrayIterator type](https://www.php.net/manual/en/class.arrayiterator.php)*
### Variable access

To access the variable, you can need to use the object operator (`->`):
```php
<?php
$forecast->current->temperature;
$forecast->hourly[0]->temperature;
$timeMachine->data[0]->weather  // cloudy
```

Some variables are grouped into logical groups, just like in the API response. You can access the actual data with chained object operators (`->`) operators:
```php
<?php
$forecast->current->wind->speed;
```

### Tests
The unit tests are written using `PHPUnit`. You need to provide your actual API key using environment variable. To run the tests, use:
```bash
# Change this to your actual API key
export METEOSOURCE_API_KEY='abcdefghijklmnopqrstuvwxyz0123456789ABCD'
vendor/bin/phpunit tests
```

### Contact us

You can contact us [here](https://www.meteosource.com/contact).