<?php
declare(strict_types=1);

namespace CarApiSdk;

class AuthDto implements \JsonSerializable
{
    private string $token;
    private string $secret;

    public function __construct(string $token, string $secret)
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    public static function build(CarApiConfig $config): self
    {
        return new self($config->token, $config->secret);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            'api_token' => $this->token,
            'api_secret' => $this->secret,
        ];
    }
}