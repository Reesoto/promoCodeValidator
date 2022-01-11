<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PromoCodeService
{
    const URL_PROMO_CODE_LIST = "https://601025826c21e10017050013.mockapi.io/ekwatest/promoCodeList";
    const CACHE_EXPIRATION_TIME = 300; // 5 minutes

    private HttpClientInterface $client;
    private CacheInterface $cache;

    /**
     * @param HttpClientInterface $client
     * @param CacheInterface $cache
     */
    public function __construct(HttpClientInterface $client, CacheInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    /**
     * Make an API call to get all promo code list as an array
     *
     * @return array
     */
    public function getPromoCodeList(): array
    {
        $promoListCodes = $this->cache->get("promo-list-code", function(ItemInterface $item){
            $item->expiresAfter(self::CACHE_EXPIRATION_TIME);
            return $this->callAPIPromoCodeList();
        });

        return $promoListCodes;
    }

    /**
     * API call to get all promo code list
     *
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function callAPIPromoCodeList() : array {
        $response = $this->client->request(
            'GET',
            self::URL_PROMO_CODE_LIST
        );

        $statusCode = $response->getStatusCode();
        if(200 === $statusCode)
        {
            $this->getValidateCode($response->toArray());
            return $response->toArray();
        } else {
            throw new HttpException($statusCode, "An error has occurred when trying to reach : " . self::URL_PROMO_CODE_LIST);
        }
    }

    /**
     * Create a formatted array of promo code to get the promo code as key
     *
     * @param array $codes
     * @return array
     */
    private function formatPromoCodeAsKey(array $codes) : array
    {
        $promoCodes = [];
        foreach ($codes as $code)
        {
            $promoCodes[$code['code']] = [
                'discountValue' => $code['discountValue'],
                'endDate' => $code['endDate']
            ];
        }

        return $promoCodes;
    }

    /**
     * Verification of promo code (if it exists and if it's not out dated)
     *
     * @param string $code
     * @return bool
     */
    public function checkCodeValidity(string $code)
    {
        $promoCodes = $this->getPromoCodeList();
        $validPromoCodes = $this->getValidateCode($promoCodes);
        $validPromoCodes = $this->formatPromoCodeAsKey($validPromoCodes);

        return $this->isCodeExist($code, $validPromoCodes);
    }

    /**
     * Check existence of a promo code (if it exists or not outdated)
     *
     * @param string $code
     * @param array $validCodes
     * @return bool
     */
    public function isCodeExist(string $code, array $validCodes)
    {
        return array_key_exists($code, $validCodes);
    }

    /**
     * Get promo code details
     *
     * @param string $code
     * @return array|mixed
     */
    public function getPromoCodeDetails(string $code)
    {
        $promoCodes = $this->getPromoCodeList();
        $promoCodeDetails = [];

        foreach ($promoCodes as $k => $promoCode)
        {
            if($promoCode['code'] === $code)
            {
                $promoCodeDetails = $promoCodes[$k];
                break;
            }
        }

        return $promoCodeDetails;
    }

    /**
     * Format data and Json encode of compatible offers according to a specific promo code
     *
     * @param array $promoCodeDetails
     * @param array $offerListDetails
     * @return false|string
     */
    public function formatCompatibleOfferList(array $promoCodeDetails, array $offerListDetails)
    {

        $compatibleOfferList = [
            "promoCode"             => $promoCodeDetails['code'],
            "endDate"               => $promoCodeDetails['endDate'],
            "discountValue"         => $promoCodeDetails['discountValue'],
            "compatibleOfferList"   => $offerListDetails
        ];

        return json_encode($compatibleOfferList);
    }

    /**
     * Create an array of valid promo code (code which hasn't expired yet)
     *
     * @param array $codes
     * @return array
     */
    public function getValidateCode(array $codes) {
        $today = date('Y-m-d');
        $validateCodes = [];

        foreach ($codes as $code) {
            $time = strtotime($code['endDate']);
            $promoEndDate = date('Y-m-d', $time);

            if($today <= $promoEndDate) {
                $validateCodes[] = $code;
            }
        }

        return $validateCodes;
    }
}