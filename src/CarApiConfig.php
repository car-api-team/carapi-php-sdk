<?php
declare(strict_types=1);

namespace CarApiSdk;

class CarApiConfig
{
    public string $token;
    public string $secret;
    public ?string $host;

    public string $httpVersion;
    /** @var string[]  */
    public array $encoding;

    /**
     * Constructor
     *
     * @param string      $token       Your token
     * @param string      $secret      Your secret
     * @param string|null $host        Defaults to carapi.app and should be left null
     * @param string      $httpVersion Defaults to HTTP 2
     * @param array      $encoding    Sets the accepts-encoding request header, default: []. To enable decoding
     *                                set this option to ['gzip'] and ensure you have the gzip extension loaded.
     */
    public function __construct(
        string $token,
        string $secret,
        ?string $host = null,
        string $httpVersion = '1.1',
        array $encoding = []
    ) {
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
     * @throws CarApiException
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
            $configs['htttVersion'] ?? '1.1',
            $configs['encoding'] ?? [],
        );
    }
}
