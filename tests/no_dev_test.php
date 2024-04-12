<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use CarApiSdk\CarApi;
use CarApiSdk\JsonSearch;
use CarApiSdk\JsonSearchItem;
var_dump($_ENV['CARPI_TOKEN']);
$sdk = CarApi::build([
    'token' => $_ENV['CARPI_TOKEN'],
    'secret' => $_ENV['CARPI_SECRET'],
]);
$sdk->authenticate();
$sdk->years(['query' => ['make' => 'Tesla']]);
$sdk->makes(['query' => ['limit' => 10, 'page' => 1]]);
$sdk->models(['query' => ['make' => 'Tesla', 'limit' => 1]]);
$json = new JsonSearch();
$json->addItem(new JsonSearchItem('make', 'like', 'Tesla'));
$sdk->trims(['query' => ['json' => $json, 'limit' => 1]]);
$sdk->bodies(['query' => ['make' => 'Tesla', 'limit' => 1]]);
$sdk->engines(['query' => ['make' => 'Tesla', 'limit' => 1]]);
$sdk->mileages(['query' => ['make' => 'Tesla', 'limit' => 1]]);
$sdk->vin('1GTG6CEN0L1139305');
$sdk->interiorColors(['query' => ['make' => 'Tesla', 'limit' => 1]]);
$sdk->exteriorColors(['query' => ['make' => 'Tesla', 'limit' => 1]]);
$sdk->vehicleAttributes('bodies.type');
$sdk->accountRequests();
$sdk->accountRequestsToday();

println('Done!');