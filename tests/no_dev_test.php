<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use CarApiSdk\CarApi;
var_dump(getenv());
var_dump(getenv('CARPI_TOKEN'));
$sdk = CarApi::build([
    'token' => getenv('CARPI_TOKEN'),
    'secret' => getenv('CARPI_SECRET'),
]);
$sdk->authenticate();
print_r($sdk->years(['query' => ['make' => 'Tesla']]));

println('Done!');