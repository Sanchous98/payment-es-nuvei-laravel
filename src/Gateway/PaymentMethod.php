<?php

namespace PaymentSystem\Laravel\Nuvei\Gateway;

use EventSauce\EventSourcing\AggregateRootId;
use PaymentSystem\Contracts\SourceInterface;
use PaymentSystem\Gateway\Resources\PaymentMethodInterface;
use PaymentSystem\Laravel\Uuid;
use PaymentSystem\PaymentMethodAggregateRoot;
use PaymentSystem\ValueObjects\BillingAddress;
use PaymentSystem\ValueObjects\GenericId;

readonly class PaymentMethod implements PaymentMethodInterface
{
    /**
     * @param array{
     * merchantId: string,
     * merchantSiteId: string,
     * internalRequestId: integer,
     * clientRequestId: mixed,
     * status: "SUCCESS"|"ERROR",
     * errCode: integer,
     * reason: string,
     * version: "1",
     * userPaymentOptionId: string,
     * cardType: string,
     * ccToken: string,
     * brand: string,
     * uniqueCC: string,
     * bin: string,
     * last4Digits: string
     * } $data
     */
    public function __construct(
        public Uuid $accountId,
        public BillingAddress $address,
        public SourceInterface $source,
        public array $data,
    ) {
    }

    public static function fromOriginal(Uuid $accountId, PaymentMethodAggregateRoot $original, array $data): self
    {
        return new self($accountId, $original->getBillingAddress(), $original->getSource(), $data);
    }

    public function getId(): AggregateRootId
    {
        return new GenericId($this->data['userPaymentOptionId']);
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
        return strtoupper($this->data['status']) === 'SUCCESS';
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->address;
    }

    public function getSource(): SourceInterface
    {
        return $this->source;
    }
}