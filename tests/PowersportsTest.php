<?php
declare(strict_types=1);

namespace Test;

use CarApiSdk\CarApiConfig;
use CarApiSdk\CarApiException;
use CarApiSdk\JsonSearch;
use CarApiSdk\JsonSearchItem;
use CarApiSdk\Powersports;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18Client;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PowersportsTest extends TestCase
{
    use TestHelperTrait;

    /**
     * @dataProvider dataProviderForMethods
     */
    public function test_methods_work(string $method): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '{"data": []}');
        $sdk = new Powersports($config, $client);
        $obj = $sdk->{$method}();
        $this->assertObjectHasProperty('data', $obj);
    }

    public static function dataProviderForMethods(): array
    {
        return [
            ['makes'],
            ['models'],
        ];
    }

    public function test_years(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '["data"]');
        $sdk = new Powersports($config, $client);
        $arr = $sdk->years();
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
        $sdk = new Powersports($config, $client);

        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage('ExceptionName: Internal Error while requesting /url/path');
        $sdk->years();
    }

    public function test_malformed_json_response(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, 'bad json');
        $sdk = new Powersports($config, $client);

        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage('Error decoding response');
        $sdk->years();
    }

    public function test_query_params(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '{"data": []}');
        $sdk = new Powersports($config, $client);

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

        $sdk = new Powersports($config, $clientMock);
        $arr = $sdk->years();
        $this->assertNotEmpty($arr);
    }
}