# CarAPI PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/car-api-team/carapi-php-sdk.svg?style=flat-square)](https://packagist.org/packages/car-api-team/carapi-php-sdk)
[![Build](https://github.com/car-api-team/carapi-php-sdk/actions/workflows/build.yml/badge.svg)](https://github.com/car-api-team/carapi-php-sdk/actions/workflows/build.yml)
[![Coverage Status](https://coveralls.io/repos/github/car-api-team/carapi-php-sdk/badge.svg?branch=main)](https://coveralls.io/github/car-api-team/carapi-php-sdk?branch=main)

PHP ^7.4 and ^8.0 compatible SDK for the developer friendly vehicle API. Please review our documentation for a better 
understanding of how this SDK works:

- https://carapi.app/docs/
- https://carapi.app/api

## Install

Install the SDK using [composer](https://getcomposer.org/):

```console
composer require car-api-team/carapi-php-sdk
```

If your project has a discoverable HTTP client then the SDK will use that automatically. If it does not, you will 
need to add one. You can read more about HTTP discovery here: https://github.com/php-http/discovery

## Usage

Create the SDK instance using your token and secret. The following example assumes you've stored them in an `.env` 
file, but you may load them in as you please.

```php
$sdk = \CarApiSdk\CarApi::build([
    'token' => getenv('CARAPI_TOKEN'),
    'secret' => getenv('CARAPI_SECRET'),
]);
```

You have now created an instance of the SDK.

### Other Options

You may also set `httpVersion` and `encoding`. The HTTP version defaults to 1.1 and we recommend keeping it at that 
version. Encoding is off by default, but GZIP is supported (note: you will need the zlib extension loaded). Example:

```php
$sdk = \CarApiSdk\CarApi::build([
    'token' => getenv('CARAPI_TOKEN'),
    'secret' => getenv('CARAPI_SECRET'),
    'httpVersion' => '2.0', // we recommend keeping the default 1.1
    'encoding' => ['gzip'],
]);
```

### Authentication

The authenticate method will both return a JWT and store the JWT in the SDK internally. There is no persistent 
storage offered, so you will need to handle caching in your application.  We'll provide a basic cache example 
using the file system, but we recommend using your frameworks caching library or something like symfony/cache 
or cake/cache.

```php
$filePath = '/some/path/not/readable/by/browsers/carapi_jwt.txt';
$jwt = file_get_contents($filePath);
if (empty($jwt) || $sdk->loadJwt($jwt)->isJwtExpired() !== false) {
    try {
        $jwt = $sdk->authenticate();
        file_put_contents($filePath, $jwt);
    } catch (\CarApiSdk\CarApiException $e) {
        // handle errors here
    }
}

// make your api calls here...
```

### Passing query parameters

Query parameters can be passed to api endpoints as key-value arrays.

```php
$sdk->years(['query' => ['make' => 'Tesla']]);
```

### Passing JSON searches

JSON search parameters can be passed to api endpoints as objects:

```php
$json = (new \CarApiSdk\JsonSearch())
    ->addItem(new \CarApiSdk\JsonSearchItem('make', 'in', ['Tesla']));
$sdk->years(['query' => ['json' => $json]])
```

### Pagination

Endpoints supporting pagination will return a collection property storing the pagination metadata and a data property 
storing the actual results. Here is an example of paging through the `/api/makes` endpoint:

```php
$page = 1;
do {
    $result = $sdk->makes(['query' => ['limit' => 1, 'page' => $page]]);
    $lastPage = $result->collection->pages;
    $page++;
    print_r($result->data);
} while ($page <= $lastPage);
```

### Exceptions

The SDK will throw \CarApiSdk\CarApiException on errors. In some cases, this is just catching and rethrowing 
underlying HTTP Exceptions or JSON Exceptions. In most cases, this will capture errors from the API response 
and format them into a CarApiException.

### Years

The years method returns an array of integers.

```php
$years = $sdk->years();
foreach ($years as $year) {
    echo $year;
}
```

Get all years that Tesla sold cars:

```php
$sdk->years(['query' => ['make' => 'Tesla']]);
```

### Makes

Returns a collection.

```php
foreach ($sdk->makes()->data as $make) {
    echo $make->name;
}
```

Get all makes for 2020:

```php
$sdk->makes(['query' => ['year' => 2020]]);
```

### Models

Returns a collection.

```php
foreach ($sdk->models()->data as $model) {
    echo $model->name;
}
```

Getting all 2020 Toyota models:

```php
$sdk->models(['query' => ['year' => 2020, 'make' => 'Toyota']]);
```

### Trims

Returns a collection.

```php
foreach ($sdk->trims()->data as $trim) {
    echo $trim->name;
}
```

Getting all 2020 Ford F-150 trims:

```php
$sdk->trims(['query' => ['year' => 2020, 'make' => 'Ford', 'model' => 'F-150']]);
```

Getting all 2020 Ford F-150 and F-250 trims:

```php
$json = (new \CarApiSdk\JsonSearch())
    ->addItem(new \CarApiSdk\JsonSearchItem('model', 'in', ['F-150', 'F-250']));
$sdk->trims(['query' => ['year' => 2020, 'make' => 'Ford', 'json' => $json]]);
```

Get all sedans by Toyota or Ford in 2020:

```php
$json = (new \CarApiSdk\JsonSearch())
    ->addItem(new \CarApiSdk\JsonSearchItem('make', 'in', ['Toyota', 'Ford']));
    ->addItem(new \CarApiSdk\JsonSearchItem('bodies.type', '=', 'Sedan'));
$result = $sdk->trims(['query' => ['year' => 2020, 'json' => $json]]);
foreach ($result->data as $trim) {
    echo $trim->name;
}
```

Or for a single trim an object is returned:

```php
echo $sdk->trimItem($id)->name;
```

### Vin

Returns an object

```php
$sdk->vin('1GTG6CEN0L1139305');
```

Loop through all trims returned by a vin lookup:

```php
foreach ($sdk->vin('1GTG6CEN0L1139305')->trims as $trim) {
    echo $trim->name;
}
```

### Bodies

Returns a collection.

```php
foreach ($sdk->bodies()->data as $body) {
    echo $body->type;
}
```

### Engines

Returns a collection.

```php
foreach ($sdk->engines()->data as $engine) {
    echo $engine->engine_type;
}
```

### Mileages

Returns a collection.

```php
$sdk->mileages();
```

### Interior Colors

Returns a collection.

```php
$sdk->interiorColors();
```

### Exterior Colors

Returns a collection.

```php
$sdk->exteriorColors();
```

### License Plate

Returns an object.

```php
$sdk->licensePlate('US', 'LNP8460#TEST', 'NY');
```

### OBD Diagnostic Code Search

Returns a collection.

```php
$sdk->obdCodes();
```

### Get single OBD Diagnostic Code

Returns an object.

```php
$sdk->obdCodeItem('B1200');
```

### CSV Datafeed

Returns the datafeed as a ResponseInterface. You will need handle extracting the file out in your application.

```php
$sdk->csvDataFeed();
```

### CSV Datafeed Last Update

Returns an object.

```php
$sdk->csvDataFeedLastUpdated();
```


### Vehicle Attributes

Returns an array of strings.

```php
$sdk->vehicleAttributes('bodies.type');
```

### Account Requests

Returns an array of objects.

```php
$sdk->accountRequests();
```

### Account Requests Today

Returns an object:

```php
$sdk->accountRequestsToday();
```
