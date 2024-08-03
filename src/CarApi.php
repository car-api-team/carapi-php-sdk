<?php
declare(strict_types=1);

namespace CarApiSdk;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18Client;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class CarApi
{
    private string $host;
    private CarApiConfig $config;
    private Psr18Client $client;
    private StreamFactoryInterface $streamFactory;
    private UriFactoryInterface $uriFactory;
    private string $jwt;

    /**
     * Construct
     *
     * @param CarApiConfig     $config An instance of CarApiConfig
     * @param Psr18Client|null $client If left null an instance will be created automatically
     */
    public function __construct(CarApiConfig $config, Psr18Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? new Psr18Client();
        $this->host = ($config->host ?? 'https://carapi.app') . '/api';
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $this->uriFactory = Psr17FactoryDiscovery::findUriFactory();
    }

    /**
     * Builds the SDK. Look at CarApiConfig for all options possible.
     *
     * @param array $options See CarApiConfig for options
     *
     * @return self
     */
    public static function build(array $options): self
    {
        return new self(CarApiConfig::build($options));
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
        } catch (\JsonException $e) {
            throw new CarApiException('Unable to build JSON payload', 500, $e);
        }

        $request = $this->client->createRequest('POST', sprintf('%s/auth/login', $this->host))
            ->withHeader('accept', 'text/plain')
            ->withHeader('content-type', 'application/json')
            ->withBody($this->streamFactory->createStream($json));

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

        if (in_array('gzip', $this->config->encoding) && \extension_loaded('zlib')) {
            $body = gzdecode($body);
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
     * Return vehicle years.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return array
     * @throws CarApiException
     */
    public function years(array $options = []): array
    {
        return $this->getDecoded('/years', $options, true);
    }

    /**
     * Return vehicle makes.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function makes(array $options = []): \stdClass
    {
        return $this->getDecoded('/makes', $options);
    }

    /**
     * Return vehicle models
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function models(array $options = []): \stdClass
    {
        return $this->getDecoded('/models', $options);
    }

    /**
     * Return vehicle trims
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function trims(array $options = []): \stdClass
    {
        return $this->getDecoded('/trims', $options);
    }

    /**
     * Return a single vehicle trim.
     *
     * @param int $id The ID of the Trim
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function trimItem(int $id): \stdClass
    {
        return $this->getDecoded(sprintf('/trims/%s', $id));
    }

    /**
     * Return a VIN.
     *
     * @param string $vin     The Vehicle Identification Number
     * @param array  $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function vin(string $vin, array $options = []): \stdClass
    {
        return $this->getDecoded(sprintf('/vin/%s', $vin), $options);
    }

    /**
     * Return vehicle bodies.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function bodies(array $options = []): \stdClass
    {
        return $this->getDecoded('/bodies', $options);
    }

    /**
     * Return vehicle engines.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function engines(array $options = []): \stdClass
    {
        return $this->getDecoded('/engines', $options);
    }

    /**
     * Return vehicle mileages.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function mileages(array $options = []): \stdClass
    {
        return $this->getDecoded('/mileages', $options);
    }

    /**
     * Return vehicle interior colors.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function interiorColors(array $options = []): \stdClass
    {
        return $this->getDecoded('/interior-colors', $options);
    }

    /**
     * Return vehicle exterior colors.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function exteriorColors(array $options = []): \stdClass
    {
        return $this->getDecoded('/exterior-colors', $options);
    }

    /**
     * Return vehicle attributes.
     *
     * @param string $attribute The name of the attribute
     *
     * @return array
     * @throws CarApiException
     */
    public function vehicleAttributes(string $attribute): array
    {
        return $this->getDecoded('/vehicle-attributes', ['query' => ['attribute' => $attribute]], true);
    }

    /**
     * Return a history of total requests made by your account.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function accountRequests(): \stdClass
    {
        return $this->getDecoded('/account/requests');
    }

    /**
     * Return requests made by your account today.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function accountRequestsToday(): \stdClass
    {
        return $this->getDecoded('/account/requests-today');
    }

    /**
     * Returns the datafeed result as a ResponseInterface
     *
     * @return ResponseInterface
     * @throws CarApiException
     */
    public function csvDataFeed(): ResponseInterface
    {
        $uri = $this->uriFactory->createUri($this->host . '/data-feeds/download');

        $request = $this->client->createRequest('GET', $uri)
            ->withHeader('accept', 'text/plain');

        if (!empty($this->jwt)) {
            $request = $request->withHeader('Authorization', sprintf('Bearer %s', $this->jwt));
        }

        return $this->sendRequest($request);
    }

    /**
     * Returns when the csv data feed was last modified
     *
     * @return \StdClass
     * @throws CarApiException
     */
    public function csvDataFeedLastUpdated(): \StdClass
    {
        return $this->getDecoded('/data-feeds/last-updated');
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
    private function getDecoded(string $url, array $options = [], ?bool $associative = null)
    {
        $response = $this->get($url, $options);
        $body = (string) $response->getBody();

        if (in_array('gzip', $this->config->encoding) && \extension_loaded('zlib')) {
            $body = gzdecode($body);
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
