# CarAPI PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/car-api-team/carapi-php-sdk.svg?style=flat-square)](https://packagist.org/packages/car-api-team/carapi-php-sdk)
[![Build](https://github.com/car-api-team/carapi-php-sdk/actions/workflows/build.yml/badge.svg)](https://github.com/car-api-team/carapi-php-sdk/actions/workflows/build.yml)
[![Coverage Status](https://coveralls.io/repos/github/car-api-team/carapi-php-sdk/badge.svg?branch=main)](https://coveralls.io/github/car-api-team/carapi-php-sdk?branch=main)

PHP ^7.4 and ^8.0 compatible SDK for the developer friendly vehicle API. Please review our documentation for a better 
understanding of how this SDK works: https://carapi.app/docs/


<!-- TOC -->
* [CarAPI PHP SDK](#carapi-php-sdk)
  * [Install](#install)
  * [General Usage](#general-usage)
    * [Other Options](#other-options)
    * [Authentication](#authentication)
    * [Passing query parameters](#passing-query-parameters)
    * [Passing JSON searches](#passing-json-searches)
    * [Pagination](#pagination)
    * [Exceptions](#exceptions)
  * [V2 OEM API Methods](#v2-oem-api-methods)
  * [V1 API Methods](#v1-api-methods)
<!-- TOC -->

## Install

Install the SDK using [composer](https://getcomposer.org/):

```console
composer require car-api-team/carapi-php-sdk
```

If your project has a discoverable HTTP client then the SDK will use that automatically. If it does not, you will 
need to add one. You can read more about HTTP discovery here: https://github.com/php-http/discovery

## General Usage

Create the SDK instance using your token and secret. The following example assumes you've stored them in an `.env` 
file, but you may load them in as you please.

For users on the V2 OEM API:

```php
$sdk = \CarApiSdk\CarApiOem::build([
    'token' => getenv('CARAPI_TOKEN'),
    'secret' => getenv('CARAPI_SECRET'),
]);
```

For users on the V1 API: 

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

For users on the V2 OEM API:

```php
$sdk = \CarApiSdk\CarApiOem::build([
    'token' => getenv('CARAPI_TOKEN'),
    'secret' => getenv('CARAPI_SECRET'),
    'httpVersion' => '2.0', // we recommend keeping the default 1.1
    'encoding' => ['gzip'],
]);
```

For users on the V1 API:

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

Authentication works the same regardless of which version of the API you are on.

### Passing query parameters

Query parameters can be passed to api endpoints as key-value arrays.

```php
$sdk->years(['query' => ['make' => 'Tesla']]);
```

This works the same in both versions.

### Passing JSON searches

JSON search parameters can be passed to api endpoints as objects:

```php
$json = (new \CarApiSdk\JsonSearch())
    ->addItem(new \CarApiSdk\JsonSearchItem('make', 'in', ['Tesla']));
$sdk->years(['query' => ['json' => $json]])
```

This works the same in both versions.

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

This works the same in both versions.

### Exceptions

The SDK will throw \CarApiSdk\CarApiException on errors. In some cases, this is just catching and rethrowing 
underlying HTTP Exceptions or JSON Exceptions. In most cases, this will capture errors from the API response 
and format them into a CarApiException.

## V2 OEM API Methods

Browse the [V2 OEM API methods](/docs/v2.md). These methods call the following endpoints: https://api.carapi.app/oem


## V1 API Methods

Browse the [V1 API methods](/docs/v1.md). These methods call the following endpoints: https://carapi.app/api