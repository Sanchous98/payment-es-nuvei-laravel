<?php

namespace PaymentSystem\Laravel\Nuvei\Gateway;

use EventSauce\EventSourcing\AggregateRootId;
use Money\Money;
use PaymentSystem\Gateway\Resources\RefundInterface;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\RefundAggregateRoot;
use PaymentSystem\ValueObjects\GenericId;

readonly class Refund implements RefundInterface
{
    /**
     * @param array{
     * merchantId: string,
     * merchantSiteId: string,
     * internalRequestId: integer,
     * transactionId: string,
     * externalTransactionId: string,
     * status: "SUCCESS"|"ERROR",
     * isAFT: "True"|"FALSE",
     * transactionStatus: "APPROVED"|"DECLINED"|"ERROR",
     * authCode: string,
     * errCode: integer,
     * errReason: string,
     * paymentMethodErrorCode: integer,
     * paymentMethodErrorReason: string,
     * gwErrorCode: integer,
     * gwErrorReason: string,
     * gwExtendedErrorCode: integer,
     * customData: string,
     * version: "1.0",
     * merchantAdviceCode: "01"|"02"|"03"|"04"|"21"|"24"|"25"|"26"|"27"|"28"|"29"|"30"|"40"|"41"|"42"|"43"
     * } $data
     */
    public function __construct(
        public Uuid $accountId,
        public Money $money,
        public AggregateRootId $paymentIntentId,
        public array $data,
    ) {
    }

    public static function fromOriginal(Uuid $accountId, RefundAggregateRoot $refund, array $data): self
    {
        return new self($accountId, $refund->getMoney(), $refund->getPaymentIntentId(), $data);
    }

    public function getId(): AggregateRootId
    {
        return new GenericId($this->data['transactionId']);
    }

    public function getGatewayId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function getRawData(): array
    {
        return $this->data;
    }

    public function isValid(): bool
    {
        return strtoupper($this->data['status']) === 'SUCCESS' && strtoupper($this->data['transactionStatus']) === 'APPROVED';
    }

    public function getFee(): ?Money
    {
        return null;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getPaymentIntentId(): AggregateRootId
    {
        return $this->paymentIntentId;
    }
}