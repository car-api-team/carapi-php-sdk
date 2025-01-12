<?php
declare(strict_types=1);

namespace Test;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18Client;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;

trait TestHelperTrait
{
    /**
     * @param int $statusCode
     * @param string $responseBody
     * @return MockObject&Psr18Client
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function createMockClient(int $statusCode, string $responseBody, array $headers = []): MockObject
    {
        $responseMock = $this->createPartialMock(Response::class, [
            'getStatusCode',
            'getBody',
            'getHeader',
        ]);
        $stream = Psr17FactoryDiscovery::findStreamFactory()->createStream($responseBody);
        $responseMock->method('getStatusCode')->willReturn($statusCode);
        $responseMock->method('getBody')->willReturn($stream);
        $responseMock->method('getHeader')->willReturn($headers);
        $clientMock = $this->createPartialMock(Psr18Client::class, ['sendRequest']);
        $clientMock->method('sendRequest')->willReturn($responseMock);

        return $clientMock;
    }
}