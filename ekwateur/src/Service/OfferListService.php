<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OfferListService
{
    const PATH_OFFER_LIST = 'https://601025826c21e10017050013.mockapi.io/ekwatest/offerList';
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
     * Make an API call to get all offers list as an array
     *
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getOfferList()
    {
        $offersList = $this->cache->get("offer-list", function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_EXPIRATION_TIME);
            return $this->callAPIOfferList();
        });

        return $offersList;
    }

    /**
     * API call to get all offers list
     *
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function callAPIOfferList() : array
    {
            $response = $this->client->request(
                'GET',
                self::PATH_OFFER_LIST
            );

            $statusCode = $response->getStatusCode();
            if(200 === $statusCode)
            {
                return $response->toArray();
            } else {
                throw new HttpException($statusCode, "An error has occurred when trying to reach : " . self::PATH_OFFER_LIST);
            }
    }

    /**
     * Return a formatted array of offer list related to a specific promo code
     *
     * @param $promoCode
     * @return array
     */
    public function getOfferListByPromoCode($promoCode)
    {
        $offers = $this->getOfferList();
        $offersByPromoCode = [];

        foreach ($offers as $offer)
        {
            $validPromoCodes = array_flip($offer['validPromoCodeList']);

            if(array_key_exists($promoCode, $validPromoCodes))
            {
                $offersByPromoCode[] = [
                    "name"  => $offer['offerName'],
                    "type"  => $offer['offerType']
                ];
            }
        }
        return $offersByPromoCode;
    }
}