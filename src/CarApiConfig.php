<?php
declare(strict_types=1);

namespace CarApiSdk;

class CarApiConfig
{
    public string $token;
    public string $secret;

    public function __construct(string $token, string $secret)
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    public static function build(array $configs): self
    {
        return new self(
            $configs['token'],
            $configs['secret']
        );
    }
}