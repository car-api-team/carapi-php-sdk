<?php
declare(strict_types=1);

namespace CarApiSdk;

class CarApiConfig
{
    public string $token;
    public string $secret;
    public ?string $host;

    public string $httpVersion;
    public string $encoding;

    /**
     * Constructor
     *
     * @param string      $token  Your token
     * @param string      $secret Your secret
     * @param string|null $host   Defaults to carapi.app and should be left null
     * @param string $httpVersion Defaults to HTTP 2
     * @param string $encoding String to send in the accepts-encoding request header, default: 'gzip, deflate, br, zstd'
     */
    public function __construct(
        string $token,
        string $secret,
        ?string $host = null,
        string $httpVersion = '2',
        string $encoding = 'gzip, deflate, br, zstd'
    )
    {
        $this->token = $token;
        $this->secret = $secret;
        $this->host = $host;
        $this->httpVersion = $httpVersion;
        $this->encoding = $encoding;
    }

    /**
     * Build an instance of this from an array.
     *
     * @param array $configs See constructor for required keys.
     *
     * @return self
     */
    public static function build(array $configs): self
    {
        if (!isset($configs['token'], $configs['secret'])) {
            throw new CarApiException('Missing token and/or secret');
        }

        return new self(
            $configs['token'],
            $configs['secret'],
            $configs['host'] ?? null,
            $configs['htttVersion'] ?? '2',
            $configs['encoding'] ?? 'gzip, deflate, br, zstd',
        );
    }
}
