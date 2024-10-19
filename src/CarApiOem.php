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

class CarApiOem
{
    use RequestResponseTrait;
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
        $this->host = ($config->host ?? 'https://api.carapi.app') . '/oem';
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

        if (in_array('gzip', $this->config->encoding) && \extension_loaded('zlib')) {
            $body = gzdecode($body);
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
     * Return OEM years.
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
     * Return OEM makes.
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
     * Return OEM models
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
     * Return OEM sub-models
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function submodels(array $options = []): \stdClass
    {
        return $this->getDecoded('/submodels', $options);
    }

    /**
     * Return a single OEM sub-model.
     *
     * @param int $id The ID of the Trim
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function submodelItem(int $id): \stdClass
    {
        return $this->getDecoded(sprintf('/submodels/%s', $id));
    }

    /**
     * Return OEM trims
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
     * Return a single OEM trim.
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
}
