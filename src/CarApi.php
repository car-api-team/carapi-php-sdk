<?php
declare(strict_types=1);

namespace CarApiSdk;

use Psr\Http\Message\ResponseInterface;

class CarApi extends BaseApi
{
    /**
     * Return vehicle years.
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
     * Return vehicle makes.
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
     * Return vehicle models
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
     * Return vehicle trims
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
     * Return a single vehicle trim.
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
     * License Plate Search
     *
     * @param string      $countryCode ISO 3166-1 alpha-2 country code (two letters)
     * @param string      $lookup      The license plate (registration in some countries) to lookup
     * @param string|null $region      Province, region or state (required for US, CA and AU)
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function licensePlate(string $countryCode, string $lookup, ?string $region = null): \stdClass
    {
        return $this->getDecoded(
            '/license-plate', [
                'query' => [
                    'country_code' => $countryCode,
                    'lookup' => $lookup,
                    'region' => $region,
                ]
            ]
        );
    }

    /**
     * OBD-II Code Search
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function obdCodes(array $options = []): \stdClass
    {
        return $this->getDecoded('/obd-codes', $options);
    }

    /**
     * Get a single OBD-II Code
     *
     * @param string $code The OBD code
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function obdCodeItem(string $code): \stdClass
    {
        return $this->getDecoded(sprintf('/obd-codes/%s', $code));
    }

    /**
     * Return vehicle bodies.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function bodies(array $options = []): \stdClass
    {
        return $this->getDecoded('/bodies', $options);
    }

    /**
     * Return vehicle engines.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function engines(array $options = []): \stdClass
    {
        return $this->getDecoded('/engines', $options);
    }

    /**
     * Return vehicle mileages.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function mileages(array $options = []): \stdClass
    {
        return $this->getDecoded('/mileages', $options);
    }

    /**
     * Return vehicle interior colors.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function interiorColors(array $options = []): \stdClass
    {
        return $this->getDecoded('/interior-colors', $options);
    }

    /**
     * Return vehicle exterior colors.
     *
     * @param array $options An array of options to pass into the request.
     *
     * @return \stdClass
     * @throws CarApiException
     */
    public function exteriorColors(array $options = []): \stdClass
    {
        return $this->getDecoded('/exterior-colors', $options);
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
