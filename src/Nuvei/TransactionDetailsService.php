<?php

namespace PaymentSystem\Laravel\Nuvei\Nuvei;

use Nuvei\Api\Service\BaseService;
use Nuvei\Api\Utils;

class TransactionDetailsService extends BaseService
{
    public function getTransactionDetails(?string $transactionId = null, ?string $clientUniqueId = null)
    {
        assert($transactionId !== null || $clientUniqueId !== null);

        $checksumParametersOrder = [
            'merchantId',
            'merchantSiteId',
        ];
        $params = [
            'merchantId' => $this->client->getConfig()->getMerchantId(),
            'merchantSiteId' => $this->client->getConfig()->getMerchantSiteId(),
            'timeStamp' => date('YmdHms'),
        ];

        if ($transactionId !== null) {
            $params['transactionId'] = $transactionId;
            $checksumParametersOrder[] = 'transactionId';
        }

        if ($clientUniqueId !== null) {
            $params['clientUniqueId'] = $clientUniqueId;
            $checksumParametersOrder[] = 'clientUniqueId';
        }

        $checksumParametersOrder = [
            ...$checksumParametersOrder,
            'timeStamp',
            'merchantSecretKey'
        ];

        $params['checksum'] = Utils::calculateChecksum($params, $checksumParametersOrder, $this->client->getConfig()->getMerchantSecretKey(), $this->client->getConfig()->getHashAlgorithm());

        return $this->requestJson($params, 'getTransactionDetails.do');
    }
}