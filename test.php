<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use CarApiSdk\CarApi;
use CarApiSdk\JsonSearch;
use CarApiSdk\JsonSearchItem;
use CarApiSdk\Powersports;

/*
 * This file runs a test against production using the SDK. You must create a .env file with a TOKEN and a SECRET.
 *
 * This can be run via: php test.php
 */

$env = (new josegonzalez\Dotenv\Loader('./.env'))
    ->parse()
    ->toEnv()
    ->toArray();

if (!isset($env['TOKEN'], $env['SECRET'])) {
    throw new LogicException('An .env file is required and must contain TOKEN and SECRET.');
}

function println(string $string) {
    echo "\n\n$string\n\n";
}

$sdk = CarApi::build([
    'token' => $env['TOKEN'],
    'secret' => $env['SECRET'],
    'host' => 'http://localhost:8080',
    'httpVersion' => '1.1',
    'encoding' => ['gzip'],
]);

println('JWT:' . $sdk->authenticate());

println('Years:');
print_r($sdk->years(['query' => ['make' => 'Tesla']]));

println('Makes:');
print_r($sdk->makes(['query' => ['limit' => 1, 'page' => 0]]));

println('Models:');
print_r($sdk->models(['query' => ['make' => 'Tesla', 'limit' => 1]]));

println('Trims:');
$json = new JsonSearch();
$json->addItem(new JsonSearchItem('make', 'like', 'Tesla'));
print_r($sdk->trims(['query' => ['json' => $json, 'limit' => 1]]));

println('Trims:');
print_r($sdk->trimItem(1));

println('Bodies:');
print_r($sdk->bodies(['query' => ['make' => 'Tesla', 'limit' => 1]]));

println('Engines:');
print_r($sdk->engines(['query' => ['make' => 'Tesla', 'limit' => 1]]));

println('Mileages:');
print_r($sdk->mileages(['query' => ['make' => 'Tesla', 'limit' => 1]]));

println('VIN:');
print_r($sdk->vin('1GTG6CEN0L1139305'));

println('Interior Colors:');
print_r($sdk->interiorColors(['query' => ['make' => 'Tesla', 'limit' => 1]]));

println('Exterior Colors:');
print_r($sdk->exteriorColors(['query' => ['make' => 'Tesla', 'limit' => 1]]));

println('Vehicle Attributes:');
print_r($sdk->vehicleAttributes('bodies.type'));

println('Account Requests:');
print_r($sdk->accountRequests());

/*println('Account Requests Today:');
print_r($sdk->accountRequestsToday());*/

println('License Plate:');
print_r($sdk->licensePlate('US', 'LNP8460#TEST', 'NY'));

println('OBD Codes:');
print_r($sdk->obdCodes(['query' => ['limit' => 1]]));

println('Single OBD Code:');
print_r($sdk->obdCodeItem('B1200'));

println('Done with Vehicles!');

$sdk = Powersports::build([
    'token' => $env['TOKEN'],
    'secret' => $env['SECRET'],
    'host' => 'http://localhost:8080',
    'httpVersion' => '1.1',
    'encoding' => ['gzip'],
]);

println('JWT:' . $sdk->authenticate());

println('Years:');
print_r($sdk->years(['query' => ['make' => 'Honda', 'type' => 'street_motorcycle']]));

println('Makes:');
print_r($sdk->makes(['query' => ['limit' => 1, 'page' => 0, 'type' => 'street_motorcycle']]));

println('Models:');
print_r($sdk->models(['query' => ['make' => 'Honda', 'limit' => 1, 'type' => 'street_motorcycle']]));

println('Done with Powersports!');