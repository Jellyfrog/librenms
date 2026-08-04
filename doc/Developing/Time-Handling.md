## Time Concepts

Most times in LibreNMS are absolute points in time at which data was collected.  This means that midnight UTC is the same as 8pm in the -0400 timezone, and 8am in +0800.  For these points in time, the preference is:

- Date objects that PHP manipulates must use Carbon objects that contain timezone information. When you save them, use one of these:
  - bigint unix epoch values.
  - timestamp fields. The SQL server can convert these to the applicable timezone when it reads them.
- Dates encoded in the URL must use the unix epoch.
- Dates encoded in JSON must use the ISO8601 representation in the Zulu/UTC timezone, and the javascript converter to show them in the correct timezone.
- Dates shown in HTML pages must be converted to the timezone that the user selected (the browser timezone by default).
- Dates parsed from user input must be read with the user's selected timezone, and converted to a JSON/URL encoded format.

There are some exceptions to the above, for example scheduled maintenance, where the user wants the maintenance window to start at 9pm each night.  In this condition, we must keep the timezone together with the time information. Then we can read the time correctly, relative to the wanted timezone.

Some more notes about database fields:
- datetime fields are usually not acceptable, because they do not know the timezone. This causes problems near the boundaries of daylight savings, and also incorrect timezone estimates when the system parses them.
- timestamp fields have a maximum date in 2106 at this time. They can keep times with a granularity of microseconds (seconds by default).
- unix epoch fields have a granularity of 1 second.

## PHP Time Functions

LibreNMS uses the Carbon library for dates.  Use these functions to make new time objects:
- `Carbon::now()` - This takes no input arguments and returns the current time.
- `Carbon::createFromTimestamp()` - This takes an integer that represents the unix epoch as input.
- `Carbon::parse($time_string)` - This takes a string as input.  It correctly reads:
  - ISO8601 times with "Z" at the end as UTC times
  - ISO8601 times with a UTC offset (-1200 to +1200) at the end
  - Datetime fields from the database with no UTC offset (it uses the PHP timezone for the time)

Use these methods on Carbon objects to convert them to unix epoch timestamps or ISO8601 Zulu time strings:
- `$object->unix()`
- `$object->toIso8601ZuluString()`

The function below formats dates on web pages. But it is legacy, because a better solution exists: use JSON to get the data from an AJAX endpoint, and then use the javascript formatting functions given further down this page to format the time. Then the date format uses the locale of the end user (for example, dd/mm/yy vs mm/dd/yy):
- `Time::format()` - Takes a Carbon object and a format string as inputs. It shows the time in the user's selected timezone, with the format string.

When you use the `Time::format()` function, select one of these config options for the date format.  An example of the default output is adjacent to each option:
 - `dateformat.long` - Wed, 04 Feb 2026 09:25:00 +0800
 - `dateformat.compact` - 2026-02-04 09:25:00
 - `dateformat.byminute` - 2026-02-04 09:25
 - `dateformat.time` - 09:25:00

### Examples

If you have a timestamp field from the database that you want to show on a web page, this code is necessary:
```php
use App\Facades\LibrenmsConfig;
use LibreNMS\Util\Time;

$output = Time::format($dbtime, LibrenmsConfig::get('dateformat.long'));
```

If you have a unix epoch input that you want to show on a web page, this code is necessary:
```php
use App\Facades\LibrenmsConfig;
use LibreNMS\Util\Time;

$output = Time::format(Time::fromTimestamp($epoch), LibrenmsConfig::get('dateformat.compact'));
```

If you receive an ISO8601 date as part of data posted from an AJAX query, and you want to convert it to a unix epoch for a SQL filter, do this:
```php
use LibreNMS\Util\Time;

$epoch = Time::parse($iso8601_date)->unix();
```

If you have a timestamp field from the database that you want to send to an AJAX endpoint as ISO8601 time, do this:
```php
$jsontime = $dbtime->toIso8601ZuluString();
```

## Javascript Time Library

### User input

LibreNMS uses the moment-timezone javascript library to parse user input times in Javascript.  To use the library, include this in the script section of a laravel page:
```
<script src="{{ asset('js/RrdGraphJS/moment-timezone-with-data.js') }}"></script>
```

Now, to parse a time with the timezone, use the moment-timezone library as shown below.  The input can be a string for moment to parse, or a unix epoch.
```js
usertime = moment.tz(input, window.tz);
```

If the input was a unix epoch or a UTC time, you can use the moment format() function to show the string representation of the date in the selected timezone.

The moment object can always give an ISO8601 date with the `.toISOString()` method.

The moment object can always give a unix epoch with the `.un.ix()` method.

### AJAX queries

For AJAX queries, there is a converter function in the librenms javascript library.  This is available for all pages. Use it as follows, when the input date is in ISO8601 format:
```js
datestring = LibreNMS.Time.format(isoDate);
```

If you use a data table, it can look like this:
```
_Need an example using data-converter_
```
