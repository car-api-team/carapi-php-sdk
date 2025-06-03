<?php

namespace CarApiSdk;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18Client;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class BaseApi
{
    protected string $host;
    protected CarApiConfig $config;
    protected Psr18Client $client;
    protected StreamFactoryInterface $streamFactory;
    protected UriFactoryInterface $uriFactory;
    protected string $jwt;
    protected string $apiVersion;

    /**
     * Construct
     *
     * @param CarApiConfig     $config An instance of CarApiConfig
     * @param Psr18Client|null $client If left null an instance will be created automatically
     */
    public function __construct(CarApiConfig $config, ?Psr18Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? new Psr18Client();
        $this->host = ($config->host ?? 'https://carapi.app') . '/api';
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $this->uriFactory = Psr17FactoryDiscovery::findUriFactory();
        $this->apiVersion = $config->apiVersion === 'v2' ? '/v2' : '';
    }

    /**
     * Returns a JWT.
     *
     * @return string
     * @throws CarApiException
     */
    public function authenticate(): string
    {
        try {
            $json = json_encode(AuthDto::build($this->config), JSON_THROW_ON_ERROR);
            if ($json === false) {
                throw new \JsonException('JSON Payload is false');
            }
        } catch (\JsonException $e) {
            throw new CarApiException('Unable to build JSON payload', 500, $e);
        }

        $stream = $this->streamFactory->createStream($json);

        $request = $this->client->createRequest('POST', sprintf('%s/auth/login', $this->host))
            ->withProtocolVersion($this->config->httpVersion)
            ->withHeader('accept', 'text/plain')
            ->withHeader('content-type', 'application/json')
            ->withHeader('content-length', (string) $stream->getSize())
            ->withBody($stream);

        $response = $this->sendRequest($request);
        $body = (string) $response->getBody();
        if ($response->getStatusCode() !== 200) {
            throw new CarApiException(
                sprintf(
                    'HTTP %s - CarAPI authentication failed: %s',
                    $response->getStatusCode(),
                    $body
                )
            );
        }

        $encoding = array_map(fn (string $str) => strtolower($str), $response->getHeader('Content-Encoding'));
        if (in_array('gzip', $encoding)
            && in_array('gzip', $this->config->encoding)
            && \extension_loaded('zlib')
        ) {
            $body = gzdecode(base64_decode($body));
            if ($body === false) {
                throw new CarApiException('Unable to decompress response. Maybe try without gzip.');
            }
        }

        $pieces = explode('.', $body);
        if (count($pieces) !== 3) {
            throw new CarApiException('Invalid JWT');
        }

        return $this->jwt = $body;
    }

    /**
     * Returns a boolean indicating if the JWT has expired. If a null response is returned it means no JWT is set.
     *
     * @param int $buffer A buffer in seconds. This will check if the JWT is expired or will expire within $buffer
     *                    seconds.
     *
     * @return bool|null
     * @throws CarApiException
     */
    public function isJwtExpired(int $buffer = 60): ?bool
    {
        if (empty($this->jwt)) {
            return null;
        }

        $pieces = explode('.', $this->jwt);
        if (count($pieces) !== 3) {
            throw new CarApiException('JWT is invalid');
        }

        $payload = base64_decode($pieces[1]);
        try {
            $data = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CarApiException('Error decoding JWT', $e->getCode(), $e);
        }

        return (new \DateTime('now', new \DateTimeZone('America/New_York')))->getTimestamp() > $data->exp + $buffer;
    }

    /**
     * Loads a JWT.
     *
     * @param string $jwt The JWT to be loaded
     *
     * @return $this
     */
    public function loadJwt(string $jwt): self
    {
        $this->jwt = $jwt;

        return $this;
    }

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
    protected function getDecoded(string $url, array $options = [], ?bool $associative = null)
    {
        $response = $this->get($url, $options);
        $body = (string) $response->getBody();

        $encoding = array_map(fn (string $str) => strtolower($str), $response->getHeader('Content-Encoding'));
        if (in_array('gzip', $encoding)
            && in_array('gzip', $this->config->encoding)
            && \extension_loaded('zlib')
        ) {
            $body = gzdecode(base64_decode($body));
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
    protected function get(string $url, array $options): ResponseInterface
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
    protected function sendRequest(RequestInterface $request): ResponseInterface
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