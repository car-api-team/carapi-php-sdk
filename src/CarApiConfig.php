<?php
declare(strict_types=1);

namespace CarApiSdk;

class CarApiConfig
{
    public string $token;
    public string $secret;
    public ?string $host;

    /**
     * Constructor
     *
     * @param string $token  Your token
     * @param string $secret Your secret
     * @param string|null $host Defaults to carapi.app and should be left null
     */
    public function __construct(string $token, string $secret, ?string $host = null)
    {
        $this->token = $token;
        $this->secret = $secret;
        $this->host = $host;
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
        );
    }
}
