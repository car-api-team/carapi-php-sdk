<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use CarApiSdk\CarApi;

$sdk = CarApi::build([
    'token' => 'test',
    'secret' => 'test',
]);

echo "done";