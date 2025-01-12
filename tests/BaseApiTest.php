<?php
declare(strict_types=1);

namespace Test;

use CarApiSdk\BaseApi;
use CarApiSdk\CarApiConfig;
use CarApiSdk\CarApiException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18Client;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BaseApiTest extends TestCase
{
    /**
     * @dataProvider dataProviderForBuildOptions
     */
    public function test_build_errors_with_missing_options(array $options): void
    {
        $this->expectException(CarApiException::class);
        CarApiConfig::build($options);
    }

    public static function dataProviderForBuildOptions(): array
    {
        return [
            [[]],
            [['token' => '123']],
            [['secret' => '123']],
        ];
    }

    public function test_authenticate(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '1.2.3');
        $sdk = new BaseApi($config, $client);
        $jwt = $sdk->authenticate();
        $this->assertNotEmpty($jwt);
    }

    public function test_authenticate_with_gzip(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1', 'encoding' => ['gzip']]);
        $body = base64_encode(gzencode('1.2.3'));
        $client = $this->createMockClient(200, $body, ['Content-Encoding' => 'gzip']);
        $sdk = new BaseApi($config, $client);
        $jwt = $sdk->authenticate();
        $this->assertNotEmpty($jwt);
    }

    public function test_authenticate_fails(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(401, 'auth failed message');
        $sdk = new BaseApi($config, $client);

        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage('auth failed message');
        $sdk->authenticate();
    }

    public function test_authenticate_returns_bad_jwt(): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $client = $this->createMockClient(200, '1.2');
        $sdk = new BaseApi($config, $client);

        $this->expectException(CarApiException::class);
        $this->expectExceptionMessage('Invalid JWT');
        $sdk->authenticate();
    }

    /**
     * @dataProvider dataProviderForJwt
     */
    public function test_loaded_jwt_not_expired(?string $payload, ?bool $result): void
    {
        $config = CarApiConfig::build(['token' => '1', 'secret' => '1']);
        $sdk = new BaseApi($config);

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
        $sdk = new BaseApi($config);
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