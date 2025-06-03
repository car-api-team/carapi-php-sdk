<?php
declare(strict_types=1);

namespace CarApiSdk;

class CarApiConfig
{
    public string $token;
    public string $secret;
    public ?string $host;
    public string $httpVersion;
    public array $encoding;
    public string $apiVersion;

    /**
     * Constructor
     *
     * @param string      $token       Your token
     * @param string      $secret      Your secret
     * @param string|null $host        Defaults to carapi.app and should be left null
     * @param string      $httpVersion Defaults to HTTP 1.1
     * @param array       $encoding    Sets the accepts-encoding request header, default: []. To enable decoding
     *                                 set this option to ['gzip'] and ensure you have the gzip extension
     *                                 loaded.
     * @param string      $apiVersion  The version of the API to make requests for.
     */
    public function __construct(
        string $token,
        string $secret,
        ?string $host = null,
        string $httpVersion = '1.1',
        array $encoding = [],
        string $apiVersion = 'v2'
    ) {
        $this->token = $token;
        $this->secret = $secret;
        $this->host = $host;
        $this->httpVersion = $httpVersion;
        $this->encoding = $encoding;
        $this->apiVersion = $apiVersion;
    }

    /**
     * Build an instance of this from an array.
     *
     * @param array $configs See constructor for required keys.
     *
     * @return self
     * @throws CarApiException
     */
    public static function build(array $configs): self
    {
        if (!isset($configs['token'], $configs['secret'])) {
            throw new CarApiException('Missing token and/or secret');
        }

        $validVersions = ['v1', 'v2'];
        if (isset($configs['apiVersion']) && !in_array($configs['apiVersion'], $validVersions)) {
            throw new CarApiException(
                sprintf(
                    'Invalid API version. Must be one of (%s) but was given: %s',
                    implode(', ', $validVersions),
                    $configs['apiVersion']
                )
            );
        }

        return new self(
            $configs['token'],
            $configs['secret'],
            $configs['host'] ?? null,
            $configs['httpVersion'] ?? '1.1',
            $configs['encoding'] ?? [],
            $configs['apiVersion'] ?? 'v2',
        );
    }
}
