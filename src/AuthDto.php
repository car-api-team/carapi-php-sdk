<?php
declare(strict_types=1);

namespace CarApiSdk;

/**
 * AuthDto
 *
 * @SuppressWarnings("unused")
 */
class AuthDto implements \JsonSerializable
{
    private string $token;
    private string $secret;

    /**
     * Constructor
     *
     * @param string $token  Your token
     * @param string $secret Your secret
     */
    public function __construct(string $token, string $secret)
    {
        $this->token = $token;
        $this->secret = $secret;
    }

    /**
     * Builds an instance of this from the config instance.
     *
     * @param CarApiConfig $config The config
     *
     * @return self
     */
    public static function build(CarApiConfig $config): self
    {
        return new self($config->token, $config->secret);
    }

    /**
     * Serializes the instance into JSON.
     *
     * @see        \JsonSerializable
     * @inheritdoc
     * @return     array
     */
    public function jsonSerialize(): array
    {
        return [
            'api_token' => $this->token,
            'api_secret' => $this->secret,
        ];
    }
}