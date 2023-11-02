<?php
declare(strict_types=1);

use CarApiSdk\CarApi;
use CarApiSdk\CarApiConfig;
use CarApiSdk\CarApiException;
use CarApiSdk\JsonSearch;
use CarApiSdk\JsonSearchItem;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18Client;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CarApiTest extends TestCase
{
    public function test_authenticate(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '1.2.3');
        $sdk = new CarApi($config, $client);
        $jwt = $sdk->authenticate();
        $this->assertNotEmpty($jwt);
    }

    public function test_authenticate_fails(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(401, 'auth failed message');
        $sdk = new CarApi($config, $client);

        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage('auth failed message');
        $sdk->authenticate();
    }

    public function test_authenticate_returns_bad_jwt(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '1.2');
        $sdk = new CarApi($config, $client);

        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage('Invalid JWT');
        $sdk->authenticate();
    }

    /**
     * @dataProvider dataProviderForMethods
     */
    public function test_methods_work(string $method): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '{"data": []}');
        $sdk = new CarApi($config, $client);
        $obj = $sdk->{$method}();
        $this->assertObjectHasProperty('data', $obj);
    }

    public static function dataProviderForMethods(): array
    {
        return [
            ['makes'],
            ['models'],
            ['trims'],
            ['bodies'],
            ['mileages'],
            ['engines'],
            ['interiorColors'],
            ['exteriorColors'],
            ['accountRequests'],
            ['accountRequestsToday'],
        ];
    }

    public function test_years(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '["data"]');
        $sdk = new CarApi($config, $client);
        $arr = $sdk->years();
        $this->assertNotEmpty($arr);
    }

    public function test_trim_item(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '{"data": []}');
        $sdk = new CarApi($config, $client);
        $obj = $sdk->trimItem(1);
        $this->assertObjectHasProperty('data', $obj);
    }

    public function test_vin(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '{"data": []}');
        $sdk = new CarApi($config, $client);
        $obj = $sdk->vin('123');
        $this->assertObjectHasProperty('data', $obj);
    }

    public function test_vehicle_attributes(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '["data"]');
        $sdk = new CarApi($config, $client);
        $arr = $sdk->vehicleAttributes('tesst');
        $this->assertNotEmpty($arr);
    }

    public function test_exception_response(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(
            401,
            '{
          "exception": "ExceptionName",
          "code": 500,
          "url": "/url/path",
          "message": "Internal Error"
        }');
        $sdk = new CarApi($config, $client);

        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage('ExceptionName: Internal Error while requesting /url/path');
        $sdk->years();
    }

    public function test_malformed_json_response(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, 'bad json');
        $sdk = new CarApi($config, $client);

        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage('Error decoding response');
        $sdk->years();
    }

    public function test_query_params(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '{"data": []}');
        $sdk = new CarApi($config, $client);

        $json = (new JsonSearch())
            ->addItem(new JsonSearchItem('make', 'in', ['Tesla']));

        $arr = $sdk->models(['query' => ['json' => $json, 'year' => 2020]]);
        $this->assertNotEmpty($arr);
    }

    /**
     * @dataProvider dataProviderForJwt
     */
    public function test_loaded_jwt_not_expired(?string $payload, ?bool $result): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $sdk = new CarApi($config);

        if ($payload) {
            $jwt = sprintf(
                '%s.%s.%s',
                base64_encode('{"typ": "JWT", "alg": "HS256"}'),
                $payload,
                '123'
            );
            $this->assertEquals($result, $sdk->loadJwt($jwt)->isJwtExpired());
        } else {
            $this->assertNull($sdk->isJwtExpired());
        }
    }

    public static function dataProviderForJwt(): array
    {
        $timestamp = (new \DateTime('now', new \DateTimeZone('America/New_York')))->getTimestamp();

        return [
            [base64_encode(sprintf('{"exp": %s}', $timestamp + 86400)), false],
            [base64_encode(sprintf('{"exp": %s}', $timestamp - 86400)), true],
            [null, null],
        ];
    }

    /**
     * @dataProvider dataProviderForBadJwt
     */
    public function test_loaded_jwt_is_malformed(string $jwt, string $error): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $sdk = new CarApi($config);
        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage($error);
        $sdk->loadJwt($jwt)->isJwtExpired();
    }

    public static function dataProviderForBadJwt(): array
    {
        return [
            ['bad jwt', 'JWT is invalid'],
            ['1..3', 'Error decoding JWT'],
        ];
    }

    /**
     * @param int $statusCode
     * @param string $responseBody
     * @return MockObject&Psr18Client
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createMockClient(int $statusCode, string $responseBody): MockObject
    {
        $responseMock = $this->createPartialMock(Response::class, [
            'getStatusCode',
            'getBody',
        ]);
        $stream = Psr17FactoryDiscovery::findStreamFactory()->createStream($responseBody);
        $responseMock->method('getStatusCode')->willReturn($statusCode);
        $responseMock->method('getBody')->willReturn($stream);
        $clientMock = $this->createPartialMock(Psr18Client::class, ['sendRequest']);
        $clientMock->method('sendRequest')->willReturn($responseMock);

        return $clientMock;
    }
}