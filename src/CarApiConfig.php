<?php
declare(strict_types=1);

namespace CarApiSdk;

class CarApiConfig
{
    public string $token;
    public string $secret;

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
     * Build an instance of this from an array.
     *
     * @param array $configs See constructor for required keys.
     * 
     * @return self
     */
    public static function build(array $configs): self
    {
        return new self(
            $configs['token'],
            $configs['secret']
        );
    }
}