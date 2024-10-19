<?php

namespace CarApiSdk;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait RequestResponseTrait
{
    /**
     * Get the JWT
     *
     * @return string|null
     */
    public function getJwt(): ?string
    {
        if (empty($this->jwt)) {
            return null;
        }

        return $this->jwt;
    }

    /**
     * HTTP GET and decode the response.
     *
     * @param string    $url         The endpoint
     * @param array     $options     Options to be passed to the endpoint
     * @param bool|null $associative Whether decoding should be associative or not
     *
     * @return mixed
     * @throws CarApiException
     */
    private function getDecoded(string $url, array $options = [], ?bool $associative = null)
    {
        $response = $this->get($url, $options);
        $body = (string) $response->getBody();

        if (in_array('gzip', $this->config->encoding) && \extension_loaded('zlib')) {
            $body = gzdecode($body);
            if ($body === false) {
                throw new CarApiException('Unable to decompress response. Maybe try without gzip.');
            }
        }

        try {
            $decoded = json_decode($body, $associative, 512, JSON_THROW_ON_ERROR);
            if ($response->getStatusCode() !== 200) {
                $decoded = (object) $decoded;
                $exception = $decoded->exception ?? 'Unknown Error';
                $message = $decoded->message ?? 'Unknown Message';
                $url = $decoded->url ?? 'Unknown URL';
                throw new CarApiException(
                    "$exception: $message while requesting $url",
                    $response->getStatusCode()
                );
            }

            return $decoded;
        } catch (\JsonException $e) {
            throw new CarApiException('Error decoding response', $e->getCode(), $e);
        }
    }

    /**
     * HTTP GET request
     *
     * @param string $url     The endpoint being requested
     * @param array  $options Options to be passed to the endpoint
     *
     * @return ResponseInterface
     * @throws CarApiException
     */
    private function get(string $url, array $options): ResponseInterface
    {
        $query = array_map(
            function ($param) {
                if ($param instanceof \JsonSerializable) {
                    return json_encode($param);
                }
                return $param;
            }, $options['query'] ?? []
        );

        $uri = $this->uriFactory->createUri($this->host . $url)->withQuery(http_build_query($query));

        $request = $this->client->createRequest('GET', $uri)
            ->withHeader('accept', 'application/json');

        if (!empty($this->jwt)) {
            $request = $request->withHeader('Authorization', sprintf('Bearer %s', $this->jwt));
        }

        return $this->sendRequest($request);
    }

    /**
     * Sends the request
     *
     * @param RequestInterface $request RequestInterface instance
     *
     * @return ResponseInterface
     * @throws CarApiException
     */
    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        if (in_array('gzip', $this->config->encoding) && \extension_loaded('zlib')) {
            $request = $request->withHeader('accept-encoding', 'gzip');
        }

        try {
            return $this->client->sendRequest($request->withProtocolVersion($this->config->httpVersion));
        } catch (ClientExceptionInterface $e) {
            throw new CarApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
}