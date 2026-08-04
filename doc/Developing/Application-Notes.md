# Notes On Application Development

## LibreNMS JSON SNMP Extends

With the polling function `json_app_get`, you can easily poll complex data
with SNMP extends and JSON.

It supplies the exceptions given below.

It takes three parameters, in the order of the list below.

- Integer :: The Device ID to get it for.
- String :: The extend name. For example, if you pass 'zfs', the function
  converts it to 'nsExtendOutputFull.3.122.102.115'.
- Integer :: The minimum expected version of the JSON return.

The mandatory keys for the returned JSON are given below.

- version :: The version of the snmp extend script. It must be numeric,
  with a minimum of 1.
- error :: The error code from the snmp extend script. It must be > 0
   (the system ignores 0, and negative values are reserved)
- errorString :: Text that tells about the error.
- data :: A key with an array that contains the data to use.

The supported exceptions are given below.

- JsonAppPollingFailedException :: Empty return from SNMP.
- JsonAppParsingFailedException :: The system could not parse the JSON
- JsonAppBlankJsonException :: Blank JSON.
- JsonAppMissingKeysException :: Mandatory keys are missing.
- JsonAppWrongVersionException :: The version is older than the supported version.
- JsonAppExtendErroredException :: Polling and parsing were good, but
  the returned data has an error set. You can examine this through
  $e->getParsedJson(), and then examine the keys error and
  errorString.

You get access to the error value through $e->getCode(). You get access to
the output through $->getOutput(), only for
JsonAppParsingFailedException. You get access to the parsed JSON through
$e->getParsedJson().

Below is an example from `includes/polling/applications/zfs.inc.php`...

```php
try {
    $zfs = json_app_get($device, $name, 1)['data'];
} catch (JsonAppMissingKeysException $e) {
    //old version with out the data key
    $zfs = $e->getParsedJson();
} catch (JsonAppException $e) {
    echo PHP_EOL . $name . ':' . $e->getCode() . ':' . $e->getMessage() . PHP_EOL;
    update_application($app, $e->getCode() . ':' . $e->getMessage(), []);

    return;
}
```

### Compression

Note: `json_app_get` supports compressed data through
base64 encoded gzip. If the function finds base64 encoding on the SNMP
return, it gunzips the data and then parses it.

You can use
`https://github.com/librenms/librenms-agent/blob/master/utils/librenms_return_optimizer`
to make JSON returns smaller.

## Application Data Storage

The `$app` model is supplied for each application poller and graph.
You can get access to, and update, the `$app->data` field to keep arrays of data in
the Application model.

When you call update_application(), the system saves the `$app` model, together with
the changes to the data field.

```
// set the variable data to $foo
$app->data = [
    'item_A' => 123,
    'item_B' => 4.5,
    'type' => 'foo',
    'other_items' => [ 'a', 'b', 'c' ],
];

// save the change
$app->save();

// var_dump the contents of the variable
var_dump($app->data);
```
