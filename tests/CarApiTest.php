<?php
declare(strict_types=1);

namespace Test;

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
            ['csvDataFeedLastUpdated'],
            ['obdCodes'],
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

    public function test_license_plate(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '{"data": []}');
        $sdk = new CarApi($config, $client);
        $obj = $sdk->licensePlate('US', 'LNP8460#TEST', 'NY');
        $this->assertObjectHasProperty('data', $obj);
    }

    public function test_single_obd_code(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '{"data": []}');
        $sdk = new CarApi($config, $client);
        $obj = $sdk->obdCodeItem('B1200');
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

    public function test_csv_datafeed()
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '');
        $sdk = new CarApi($config, $client);
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $sdk->csvDataFeed());
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

    public function test_gzip_encoding(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1', 'encoding' => ['gzip']]);
        $body = base64_encode(gzencode('["data"]'));
        $clientMock = $this->createMockClient(200, $body, ['Content-Encoding' => 'gzip']);

        $sdk = new CarApi($config, $clientMock);
        $arr = $sdk->years();
        $this->assertNotEmpty($arr);
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