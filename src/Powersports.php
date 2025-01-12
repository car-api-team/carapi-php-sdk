<?php
declare(strict_types=1);

namespace CarApiSdk;

class Powersports extends BaseApi
{
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
     * Return powersports years.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return array
     * @throws CarApiException
     */
    public function years(array $options = []): array
    {
        return $this->getDecoded('/years/powersports', $options, true);
    }

    /**
     * Return powersports makes.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function makes(array $options = []): \stdClass
    {
        return $this->getDecoded('/makes/powersports', $options);
    }

    /**
     * Return powersports models
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function models(array $options = []): \stdClass
    {
        return $this->getDecoded('/models/powersports', $options);
    }
}
